<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Document,Job,Tenant};
use App\Services\Talent\CandidatePrivacyInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CandidatePrivacyInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_is_tenant_scoped_and_does_not_delete_records(): void
    {
        Storage::fake('local');
        $tenant = Tenant::create(['name'=>'Synthetic Tenant','slug'=>'synthetic-inventory']);
        $other = Tenant::create(['name'=>'Other Tenant','slug'=>'other-inventory']);
        $candidate = Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Candidate','email'=>'inventory@example.invalid']);
        $otherCandidate = Candidate::create(['tenant_id'=>$other->id,'name'=>'Other Candidate','email'=>'other@example.invalid']);
        $job = Job::create(['tenant_id'=>$tenant->id,'title'=>'Synthetic Job','slug'=>'synthetic-inventory-job','description'=>'Test']);
        $application = Application::create(['tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,'job_id'=>$job->id,'stage'=>'applied']);
        $doc = Document::create([
            'tenant_id'=>$tenant->id, 'candidate_id'=>$candidate->id,
            'application_id'=>$application->id, 'disk'=>'local',
            'storage_key'=>'talent-private/sanitized/missing.pdf',
            'original_name'=>'synthetic.pdf','mime_type'=>'application/pdf',
            'size_bytes'=>20,'scan_status'=>'clean',
        ]);
        $report = app(CandidatePrivacyInventory::class)->inspect($tenant->id, $candidate->id);
        $this->assertSame('review_only', $report['mode']);
        $this->assertFalse($report['deletion_performed']);
        $this->assertSame(1, $report['counts']['applications']);
        $this->assertSame(1, $report['counts']['documents']);
        $this->assertSame(1, $report['document_files']['missing_private_files']);
        $this->assertTrue(Document::whereKey($doc->id)->exists());
        $this->assertTrue(Candidate::whereKey($candidate->id)->exists());
        $this->assertTrue(Candidate::whereKey($otherCandidate->id)->exists());
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(CandidatePrivacyInventory::class)->inspect($other->id, $candidate->id);
    }
}
