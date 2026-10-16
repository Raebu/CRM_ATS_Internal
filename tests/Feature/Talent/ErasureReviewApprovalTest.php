<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,PrivacyRequest,Tenant};
use App\Services\Talent\ErasureReviewApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ErasureReviewApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): array
    {
        $tenant = Tenant::create(['name'=>'Approval Test','slug'=>'erasure-approval-test']);
        $candidate = Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic','email'=>'approval@example.invalid']);
        $request = PrivacyRequest::create([
            'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,'request_type'=>'erasure',
            'status'=>'in_review','created_by'=>17,'verified_at'=>now(),
            'identity_evidence_reviewed_at'=>now(),
        ]);
        $id = DB::table('talent_erasure_reviews')->insertGetId([
            'tenant_id'=>$tenant->id,'privacy_request_id'=>$request->id,
            'candidate_id'=>$candidate->id,'status'=>'draft','prepared_by'=>17,
            'inventory_digest'=>app(\App\Services\Talent\CandidateErasureFingerprint::class)->digest($tenant->id, $candidate->id),
            'legal_hold_notes'=>'Reviewed; no active hold recorded in test',
            'backup_handling_notes'=>'Backup restoration review is required',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        return [$tenant,$candidate,$request,$id];
    }

    public function test_incomplete_review_is_rejected_without_mutation(): void
    {
        [$tenant,$candidate,$request,$id] = $this->prepare();
        try {
            app(ErasureReviewApproval::class)->approve($tenant->id,$id,18);
            $this->fail('Incomplete review was approved');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('erasure_review',$exception->errors());
        }
        $this->assertSame('draft',DB::table('talent_erasure_reviews')->where('id',$id)->value('status'));
        $this->assertTrue(Candidate::whereKey($candidate->id)->exists());
    }

    public function test_separate_approver_can_approve_complete_review_without_deletion(): void
    {
        [$tenant,$candidate,$request,$id] = $this->prepare();
        foreach (ErasureReviewApproval::CATEGORIES as $category) {
            DB::table('talent_erasure_decisions')->insert([
                'tenant_id'=>$tenant->id,'review_id'=>$id,'data_category'=>$category,
                'proposed_action'=>'retain','legal_basis'=>'Test-only retention rationale',
                'rationale'=>'Review required before any execution',
                'reviewed_by'=>17,'reviewed_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
        try {
            app(ErasureReviewApproval::class)->approve($tenant->id,$id,17);
            $this->fail('Self approval was accepted');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('erasure_review',$exception->errors());
        }
        app(ErasureReviewApproval::class)->approve($tenant->id,$id,18);
        $this->assertSame('review_approved',DB::table('talent_erasure_reviews')->where('id',$id)->value('status'));
        $this->assertSame(18,(int)DB::table('talent_erasure_reviews')->where('id',$id)->value('approved_by'));
        $this->assertTrue(Candidate::whereKey($candidate->id)->exists());
        $this->assertSame('in_review',$request->fresh()->status);
    }

    public function test_candidate_record_change_invalidates_approval_even_without_count_change(): void
    {
        [$tenant,$candidate,$request,$id] = $this->prepare();
        foreach (ErasureReviewApproval::CATEGORIES as $category) {
            DB::table('talent_erasure_decisions')->insert([
                'tenant_id'=>$tenant->id,'review_id'=>$id,'data_category'=>$category,
                'proposed_action'=>'retain','legal_basis'=>'Synthetic test only',
                'rationale'=>'Synthetic review decision','reviewed_by'=>17,
                'reviewed_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
            ]);
        }
        // Direct update deliberately does not change updated_at or row counts.
        DB::table('talent_candidates')->where('id',$candidate->id)->update(['name'=>'Changed after preparation']);
        try {
            app(ErasureReviewApproval::class)->approve($tenant->id,$id,18);
            $this->fail('Stale review incorrectly approved');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('erasure_review',$exception->errors());
        }
        $this->assertSame('draft',DB::table('talent_erasure_reviews')->where('id',$id)->value('status'));
        $this->assertTrue(Candidate::whereKey($candidate->id)->exists());
    }

    public function test_fingerprint_rejects_cross_tenant_candidate_lookup(): void
    {
        [$tenant,$candidate] = $this->prepare();
        $other = Tenant::create(['name'=>'Other Tenant','slug'=>'other-tenant']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        app(\App\Services\Talent\CandidateErasureFingerprint::class)->digest($other->id,$candidate->id);
    }

}
