<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,PrivacyRequest,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

final class ErasureReviewInterfaceTest extends TestCase
{
 use RefreshDatabase;
 public function test_verified_request_can_create_draft_but_cannot_self_approve(): void
 {
  $this->withoutMiddleware(CanInstall::class);
  $role=Role::create(['name'=>'Synthetic Privacy Admin','permission_type'=>'all','permissions'=>[]]);
  $user=User::create(['name'=>'Synthetic Privacy Admin','email'=>'privacy-ui-'.uniqid().'@example.invalid','password'=>bcrypt('test-only'),'status'=>1,'role_id'=>$role->id]);
  $this->actingAs($user,'user');
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Candidate','email'=>'erasure-ui@example.invalid']);
  $request=PrivacyRequest::create(['tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,'request_type'=>'erasure','status'=>'in_review','created_by'=>$user->id,'verified_at'=>now(),'identity_evidence_reviewed_at'=>now()]);
  $this->get(route('admin.talent.erasure.show',$request->id))->assertOk();
  $this->post(route('admin.talent.erasure.prepare',$request->id))->assertRedirect();
  $review=DB::table('talent_erasure_reviews')->where('privacy_request_id',$request->id)->first();
  $this->assertNotNull($review);
  $this->assertSame(14,DB::table('talent_erasure_decisions')->where('review_id',$review->id)->count());

  DB::table('talent_erasure_decisions')->where('review_id',$review->id)
      ->where('data_category','candidate_identity')->update([
          'proposed_action'=>'retain','rationale'=>'Synthetic pre-refresh rationale',
          'legal_basis'=>'Synthetic','reviewed_by'=>$user->id,'reviewed_at'=>now(),
      ]);
  DB::table('talent_candidates')->where('id',$candidate->id)->update(['name'=>'Changed Candidate']);
  $this->post(route('admin.talent.erasure.refresh',$request->id))->assertRedirect();
  $this->assertSame('review_required',DB::table('talent_erasure_decisions')
      ->where('review_id',$review->id)->where('data_category','candidate_identity')
      ->value('proposed_action'));
  $this->assertNull(DB::table('talent_erasure_decisions')
      ->where('review_id',$review->id)->where('data_category','candidate_identity')
      ->value('rationale'));
  $this->assertSame(1,DB::table('talent_events')
      ->where('event_type','privacy.erasure_review_refreshed.v1')->count());

  $this->from(route('admin.talent.erasure.show',$request->id))
   ->post(route('admin.talent.erasure.approve',$request->id))->assertSessionHasErrors('erasure_review');
  $this->assertSame('draft',DB::table('talent_erasure_reviews')->where('id',$review->id)->value('status'));
  $this->assertTrue(Candidate::whereKey($candidate->id)->exists());
 }
}
