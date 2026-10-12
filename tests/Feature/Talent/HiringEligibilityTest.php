<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Job,Offer,Tenant};
use App\Services\Talent\HiringEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HiringEligibilityTest extends TestCase
{
 use RefreshDatabase;

 public function test_offer_for_another_application_does_not_authorize_hiring():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Second Candidate','email'=>'second@example.invalid']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Second Role','slug'=>'second-role','description'=>'Testing']);
  $a=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'offer']);
  $other=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'offer']);
  Offer::create(['tenant_id'=>$tenant->id,'application_id'=>$other->id,'version'=>1,'currency'=>'GBP','status'=>'accepted']);
  $this->expectException(ValidationException::class);
  HiringEligibility::enforce($tenant->id,$a->id);
 }

 public function test_accepted_offer_allows_hiring_for_same_application():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Eligible Candidate','email'=>'eligible@example.invalid']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Eligible Role','slug'=>'eligible-role','description'=>'Testing']);
  $application=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'offer']);
  Offer::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'version'=>1,'currency'=>'GBP','status'=>'accepted']);
  HiringEligibility::enforce($tenant->id,$application->id);
  $this->assertTrue(true);
 }

 public function test_application_without_accepted_offer_is_ineligible_for_hiring():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Candidate','email'=>'eligibility@example.invalid']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Synthetic Role','slug'=>'eligibility-role','description'=>'Testing']);
  $application=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'offer']);
  $this->expectException(ValidationException::class);
  HiringEligibility::enforce($tenant->id,$application->id);
 }
}
