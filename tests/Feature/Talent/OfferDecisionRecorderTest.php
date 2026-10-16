<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Job,Offer,Tenant};
use App\Services\Talent\OfferDecisionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfferDecisionRecorderTest extends TestCase
{
 use RefreshDatabase;

 private function fixture():array
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Candidate','email'=>'decision@example.invalid']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Role','slug'=>'decision-role','description'=>'Test']);
  $app=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'offer']);
  $offer=Offer::create(['tenant_id'=>$tenant->id,'application_id'=>$app->id,'version'=>1,'currency'=>'GBP','status'=>'sent']);
  $token=str_repeat('Z',64);
  $challenge=DB::table('talent_offer_acceptance_challenges')->insertGetId([
   'tenant_id'=>$tenant->id,'offer_id'=>$offer->id,'token_hash'=>hash('sha256',$token),
   'expires_at'=>now()->addHour(),'created_at'=>now(),'updated_at'=>now(),
  ]);
  return [$tenant,$candidate,$offer,$token,$challenge];
 }

 public function test_authenticated_candidate_decision_is_single_use_and_audited():void
 {
  [$tenant,$candidate,$offer,$token,$challenge]=$this->fixture();
  config()->set('talent.verified_offer_acceptance_enabled',true);
  $recorder=app(OfferDecisionRecorder::class);
  $this->assertTrue($recorder->record($tenant->id,$offer->id,$token,'accepted',$candidate->id));
  $this->assertFalse($recorder->record($tenant->id,$offer->id,$token,'accepted',$candidate->id));
  $this->assertDatabaseHas('talent_offers',['id'=>$offer->id,'status'=>'accepted']);
  $this->assertDatabaseHas('talent_offer_challenge_audit',['challenge_id'=>$challenge,'action'=>'accepted']);
 }
 public function test_wrong_candidate_cannot_decide():void
 {
  [$tenant,$candidate,$offer,$token]=$this->fixture();
  config()->set('talent.verified_offer_acceptance_enabled',true);
  $this->assertFalse(app(OfferDecisionRecorder::class)->record($tenant->id,$offer->id,$token,'declined',$candidate->id+999));
  $this->assertDatabaseHas('talent_offers',['id'=>$offer->id,'status'=>'sent']);
 }
 public function test_expired_or_revoked_challenge_does_not_change_offer():void
 {
  [$tenant,$candidate,$offer,$token,$challenge]=$this->fixture();
  config()->set('talent.verified_offer_acceptance_enabled',true);
  DB::table('talent_offer_acceptance_challenges')->where('id',$challenge)->update(['expires_at'=>now()->subMinute()]);
  $this->assertFalse(app(OfferDecisionRecorder::class)->record($tenant->id,$offer->id,$token,'accepted',$candidate->id));
  DB::table('talent_offer_acceptance_challenges')->where('id',$challenge)->update(['expires_at'=>now()->addHour(),'revoked_at'=>now()]);
  $this->assertFalse(app(OfferDecisionRecorder::class)->record($tenant->id,$offer->id,$token,'accepted',$candidate->id));
  $this->assertDatabaseHas('talent_offers',['id'=>$offer->id,'status'=>'sent']);
 }
 public function test_cross_tenant_decision_is_rejected():void
 {
  [$tenant,$candidate,$offer,$token]=$this->fixture();
  $other=Tenant::create(['slug'=>'other-decision-tenant','name'=>'Other Tenant']);
  config()->set('talent.verified_offer_acceptance_enabled',true);
  $this->assertFalse(app(OfferDecisionRecorder::class)->record($other->id,$offer->id,$token,'accepted',$candidate->id));
  $this->assertDatabaseHas('talent_offers',['id'=>$offer->id,'status'=>'sent']);
 }
 public function test_decline_consumes_challenge_and_records_decision():void
 {
  [$tenant,$candidate,$offer,$token,$challenge]=$this->fixture();
  config()->set('talent.verified_offer_acceptance_enabled',true);
  $this->assertTrue(app(OfferDecisionRecorder::class)->record($tenant->id,$offer->id,$token,'declined',$candidate->id));
  $this->assertDatabaseHas('talent_offers',['id'=>$offer->id,'status'=>'declined','accepted_at'=>null]);
  $this->assertDatabaseHas('talent_offer_challenge_audit',['challenge_id'=>$challenge,'action'=>'declined']);
 }
 public function test_disabled_feature_cannot_decide():void
 {
  [$tenant,$candidate,$offer,$token]=$this->fixture();
  config()->set('talent.verified_offer_acceptance_enabled',false);
  $this->assertFalse(app(OfferDecisionRecorder::class)->record($tenant->id,$offer->id,$token,'accepted',$candidate->id));
 }
}
