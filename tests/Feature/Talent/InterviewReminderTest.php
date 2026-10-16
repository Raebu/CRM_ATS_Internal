<?php
namespace Tests\Feature\Talent;
use App\Models\Talent\{Application,Candidate,Interview,Job,RecruitmentNotification,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewReminderTest extends TestCase
{
 use RefreshDatabase;
 public function test_repeating_reminder_command_does_not_duplicate_records():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Support','slug'=>'reminder-test','description'=>'Test']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Candidate','email'=>'reminder@example.invalid']);
  $application=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>'applied']);
  $interview=Interview::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'title'=>'Interview','starts_at'=>now()->addHours(12),'ends_at'=>now()->addHours(13),'status'=>'scheduled']);
  $this->artisan('talent:interview-reminders')->assertSuccessful();
  $this->artisan('talent:interview-reminders')->assertSuccessful();
  $this->assertSame(1,RecruitmentNotification::where('kind','interview_reminder')->count());
  $interview->update(['starts_at'=>now()->addHours(18),'ends_at'=>now()->addHours(19)]);
  $this->artisan('talent:interview-reminders')->assertSuccessful();
  $this->assertSame(1,RecruitmentNotification::where('kind','interview_reminder')->where('status','cancelled')->count());
  $this->assertSame(1,RecruitmentNotification::where('kind','interview_reminder')->where('status','pending')->count());
  $interview->update(['status'=>'cancelled']);
  $this->artisan('talent:interview-reminders')->assertSuccessful();
  $this->assertSame(0,RecruitmentNotification::where('kind','interview_reminder')->where('status','pending')->count());
 }
}
