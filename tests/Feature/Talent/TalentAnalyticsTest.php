<?php
namespace Tests\Feature\Talent;
use App\Models\Talent\{Application,Candidate,Job,Tenant};
use App\Http\Controllers\Talent\TalentAnalyticsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentAnalyticsTest extends TestCase
{
 use RefreshDatabase;
 public function test_analytics_excludes_other_tenant_records():void
 {
  $raeburn=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $other=Tenant::create(['slug'=>'analytics-other','name'=>'Other Tenant']);
  foreach ([$raeburn,$other] as $tenant) {
   $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Developer','slug'=>'job-'.$tenant->id,'description'=>'Role','status'=>'published']);
   $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Applicant','email'=>'analytics-'.$tenant->id.'@example.invalid']);
   Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'applied','source'=>'direct']);
  }
  $response=(new TalentAnalyticsController)->index();
  $data=$response->getData();
  $this->assertSame(1,$data['jobs']);
  $this->assertSame(1,$data['publishedJobs']);
  $this->assertSame(1,$data['applications']);
  $this->assertSame(1,(int)$data['sources']['direct']);
 }
}
