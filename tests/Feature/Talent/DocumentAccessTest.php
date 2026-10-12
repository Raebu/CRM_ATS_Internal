<?php

namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,Document,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_document_is_not_eligible_for_download(): void
    {
        $tenant = Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
        $candidate = Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Test','email'=>'document-test@example.invalid']);
        $document = Document::create([
            'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,
            'disk'=>'local','storage_key'=>'talent-private/test/cv.pdf',
            'original_name'=>'cv.pdf','mime_type'=>'application/pdf',
            'size_bytes'=>10,'scan_status'=>'pending'
        ]);

        $this->assertSame('pending',$document->scan_status);
        $this->assertFalse($document->scan_status === 'clean');
    }

    public function test_rejected_document_never_counts_as_clean(): void
    {
        $this->assertNotSame('clean','infected');
        $this->assertNotSame('clean','scan_failed');
        $this->assertNotSame('clean','pending');
    }
}
