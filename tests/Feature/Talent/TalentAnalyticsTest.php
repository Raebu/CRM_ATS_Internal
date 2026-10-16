<?php
namespace Tests\Feature\Talent;
use App\Models\Talent\{Application,Candidate,Interview,Job,Offer,Tenant};
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
   $application=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'applied','source'=>'direct']);
   Interview::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'title'=>'Screening','starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHour()]);
   Offer::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'version'=>1,'status'=>'draft']);
  }
  $response=(new TalentAnalyticsController)->index();
  $data=$response->getData();
  $this->assertSame(1,$data['jobs']);
  $this->assertSame(1,$data['publishedJobs']);
  $this->assertSame(1,$data['applications']);
  $this->assertSame(1,$data['interviews']);
  $this->assertSame(1,$data['offers']);
  $this->assertSame(0,$data['acceptedOffers']);
  $this->assertSame(100.0,$data['interviewConversion']);
  $this->assertSame(100.0,$data['offerConversion']);
  $this->assertSame(0.0,$data['acceptanceConversion']);
  $this->assertNull($data['averageTimeToAcceptedOfferDays']);
  $this->assertSame(1,(int)$data['sources']['direct']);
 }
}
