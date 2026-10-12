<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Job,Tenant};
use App\Services\Talent\HiringEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HiringEligibilityTest extends TestCase
{
 use RefreshDatabase;

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
