<?php

namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,CvProfile,Document,Job,Tenant};
use App\Services\Talent\ClamAvScanner;
use App\Services\Talent\PdfSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

class DocumentUploadWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function application(): Application
    {
        $this->withoutMiddleware(CanInstall::class);
        config()->set('talent.cv_uploads_enabled', true);
        $role=Role::create(['name'=>'Synthetic Document Admin','permission_type'=>'all','permissions'=>[]]);
        $user=User::create([
            'name'=>'Synthetic Document Admin',
            'email'=>'document-admin-'.uniqid().'@example.invalid',
            'password'=>bcrypt('testing-only'),
            'status'=>1,'role_id'=>$role->id,
        ]);
        $this->actingAs($user,'user');
        $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
        $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Candidate','email'=>'synthetic-doc@example.invalid']);
        $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Synthetic Role','slug'=>'synthetic-doc-role','description'=>'Test']);
        return Application::create(['tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,'job_id'=>$job->id,'stage'=>'applied']);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('synthetic-cv.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n");
    }

    public function test_upload_creates_review_draft_and_destroys_source(): void
    {
        Storage::fake('local');
        $application = $this->application();
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->twice()->andReturn('clean');
        $this->mock(PdfSanitizer::class)->shouldReceive('sanitize')->once()->andReturnUsing(
            function ($src, $dst) {
                Storage::disk('local')->put($dst, "%PDF-1.4\n% reconstructed\n%%EOF\n");
                return "PROFILE\nExperienced analyst\nSKILLS\nPHP and SQL";
            }
        );
        $this->post(route('admin.talent.documents.upload', $application->id), ['cv' => $this->pdf()])
            ->assertRedirect();
        $this->assertSame(0, Document::count());
        $profile = CvProfile::firstOrFail();
        $this->assertSame('review_required', $profile->status);
        $this->assertSame('PHP and SQL', $profile->fields['skills']);
        $this->assertSame([], Storage::disk('local')->allFiles('talent-private'));
    }

    public function test_verified_form_creates_recruiter_pdf_only_after_approval(): void
    {
        Storage::fake('local');
        $application = $this->application();
        $profile = CvProfile::create([
            'tenant_id' => $application->tenant_id,
            'application_id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'status' => 'review_required',
            'fields' => ['full_name' => 'Draft Candidate'],
        ]);
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->once()->andReturn('clean');
        $response = $this->post(route('admin.talent.cv-profiles.approve', $profile->id), [
            'first_name' => 'Verified',
            'last_name' => 'Candidate',
            'skills' => 'PHP, SQL',
            'employment' => 'Verified work history',
        ]);
        $response->assertRedirect();
        $this->assertSame('approved', $profile->fresh()->status);
        $document = Document::firstOrFail();
        $this->assertStringStartsWith('talent-private/sanitized/', $document->storage_key);
        Storage::disk('local')->assertExists($document->storage_key);
        $this->get(route('admin.talent.documents.download', $document->id))->assertOk();
    }

    public function test_detected_malware_is_rejected_and_not_retained(): void
    {
        Storage::fake('local');
        $application=$this->application();
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->once()->andReturn('infected');
        $this->mock(PdfSanitizer::class)->shouldNotReceive('sanitize');
        $this->from(route('admin.talent.applications.show',$application->id))
            ->post(route('admin.talent.documents.upload',$application->id),['cv'=>$this->pdf()])
            ->assertSessionHasErrors('cv');
        $this->assertSame(0,Document::count());
        $this->assertSame([],Storage::disk('local')->allFiles('talent-private'));
    }

    public function test_scanner_unavailable_fails_closed(): void
    {
        Storage::fake('local');
        $application=$this->application();
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->once()->andThrow(new \RuntimeException('Unavailable'));
        $this->mock(PdfSanitizer::class)->shouldNotReceive('sanitize');
        $this->from(route('admin.talent.applications.show',$application->id))
            ->post(route('admin.talent.documents.upload',$application->id),['cv'=>$this->pdf()])
            ->assertSessionHasErrors('cv');
        $this->assertSame(0,Document::count());
        $this->assertSame([],Storage::disk('local')->allFiles('talent-private'));
    }

    public function test_sanitizer_failure_discards_all_copies(): void
    {
        Storage::fake('local');
        $application = $this->application();
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->once()->andReturn('clean');
        $this->mock(PdfSanitizer::class)->shouldReceive('sanitize')->once()->andThrow(new \RuntimeException('Sandbox unavailable'));
        $this->post(route('admin.talent.documents.upload', $application->id), ['cv' => $this->pdf()])
            ->assertSessionHasErrors('cv');
        $this->assertSame(0, Document::count());
        $this->assertSame([], Storage::disk('local')->allFiles('talent-private'));
    }

    public function test_reconstructed_pdf_scan_failure_discards_all_copies(): void
    {
        Storage::fake('local');
        $application = $this->application();
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->twice()->andReturn('clean', 'infected');
        $this->mock(PdfSanitizer::class)->shouldReceive('sanitize')->once()->andReturnUsing(
            function ($src, $dst) { Storage::disk('local')->put($dst, "%PDF-1.4\\n%%EOF\\n"); return 'SKILLS'; }
        );
        $this->post(route('admin.talent.documents.upload', $application->id), ['cv' => $this->pdf()])
            ->assertSessionHasErrors('cv');
        $this->assertSame(0, Document::count());
        $this->assertSame([], Storage::disk('local')->allFiles('talent-private'));
    }

    public function test_legacy_clean_original_is_never_downloadable(): void
    {
        Storage::fake('local');
        $application = $this->application();
        $key = 'talent-private/legacy-test.pdf';
        Storage::disk('local')->put($key, "%PDF-1.4\\n%%EOF\\n");
        $doc = Document::create([
            'tenant_id' => $application->tenant_id,
            'candidate_id' => $application->candidate_id,
            'application_id' => $application->id,
            'disk' => 'local',
            'storage_key' => $key,
            'original_name' => 'old.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 20,
            'scan_status' => 'clean',
        ]);
        $this->get(route('admin.talent.documents.download', $doc->id))->assertNotFound();
    }

    public function test_uploads_disabled_by_default(): void
    {
        Storage::fake('local');
        $application = $this->application();
        config()->set('talent.cv_uploads_enabled', false);
        $this->post(route('admin.talent.documents.upload', $application->id), ['cv' => $this->pdf()])
            ->assertStatus(503);
        $this->assertSame(0, Document::count());
    }

    public function test_non_pdf_is_rejected_before_storage(): void
    {
        Storage::fake('local');
        $application=$this->application();
        $this->from(route('admin.talent.applications.show',$application->id))
            ->post(route('admin.talent.documents.upload',$application->id),[
                'cv'=>UploadedFile::fake()->createWithContent('test.txt','This is not a PDF'),
            ])->assertSessionHasErrors('cv');
        $this->assertSame(0,Document::count());
    }
}
