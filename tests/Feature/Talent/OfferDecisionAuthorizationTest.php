<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Job,Offer,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

class OfferDecisionAuthorizationTest extends TestCase
{
 use RefreshDatabase;
 public function test_admin_cannot_impersonate_candidate_offer_decision_even_when_feature_flag_is_enabled():void
 {
  $this->withoutMiddleware(CanInstall::class);
  $role=Role::create(['name'=>'Offer decision test','permission_type'=>'all','permissions'=>[]]);
  $user=User::create(['name'=>'Offer Tester','email'=>'offer-tester-'.uniqid().'@example.invalid','password'=>bcrypt('test-password'),'role_id'=>$role->id,'status'=>1]);
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Candidate','email'=>'offer-guard@example.invalid']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Synthetic Role','slug'=>'offer-guard-role','description'=>'Testing']);
  $application=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'offer']);
  $offer=Offer::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'version'=>1,'currency'=>'GBP','status'=>'sent']);
  config()->set('talent.verified_offer_acceptance_enabled',true);
  $this->actingAs($user,'user');
  foreach(['accepted','declined'] as $decision) {
   $this->patch(route('admin.talent.offers.update',$offer->id),['status'=>$decision])->assertSessionHasErrors('status');
   $this->assertDatabaseHas('talent_offers',['id'=>$offer->id,'status'=>'sent','accepted_at'=>null]);
  }
 }
}
