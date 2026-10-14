<?php

namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Document,Job,Tenant};
use App\Services\Talent\ClamAvScanner;
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

    public function test_clean_pdf_is_kept_private_and_downloadable(): void
    {
        Storage::fake('local');
        $application=$this->application();
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->once()->andReturn('clean');
        $this->post(route('admin.talent.documents.upload',$application->id),['cv'=>$this->pdf()])->assertRedirect();
        $document=Document::firstOrFail();
        $this->assertSame('clean',$document->scan_status);
        $this->assertStringStartsWith('talent-private/',$document->storage_key);
        Storage::disk('local')->assertExists($document->storage_key);
        $this->get(route('admin.talent.documents.download',$document->id))->assertOk();
    }

    public function test_detected_malware_is_rejected_and_not_retained(): void
    {
        Storage::fake('local');
        $application=$this->application();
        $this->mock(ClamAvScanner::class)->shouldReceive('scan')->once()->andReturn('infected');
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
        $this->from(route('admin.talent.applications.show',$application->id))
            ->post(route('admin.talent.documents.upload',$application->id),['cv'=>$this->pdf()])
            ->assertSessionHasErrors('cv');
        $this->assertSame(0,Document::count());
        $this->assertSame([],Storage::disk('local')->allFiles('talent-private'));
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
