<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Job,Tenant,Interview,Offer,StageHistory};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

class ApplicationWorkspaceTest extends TestCase
{
 use RefreshDatabase;

 public function test_authenticated_recruiter_sees_complete_application_record():void
 {
  $this->withoutMiddleware(CanInstall::class);
  $role=Role::create(['name'=>'Talent workspace test','permission_type'=>'all','permissions'=>[]]);
  $user=User::create(['name'=>'Workspace Tester','email'=>'workspace-'.uniqid().'@example.invalid','password'=>bcrypt('test-password'),'role_id'=>$role->id,'status'=>1]);
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Candidate','email'=>'synthetic@example.invalid']);
  $job=Job::create(['tenant_id'=>$tenant->id,'slug'=>'synthetic-vacancy','title'=>'Synthetic Engineer','description'=>'Testing']);
  $application=Application::create(['tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,'job_id'=>$job->id,'stage'=>'applied']);
  StageHistory::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'from_stage'=>null,'to_stage'=>'applied','actor_id'=>$user->id]);
  $interview=Interview::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'title'=>'First interview','starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHour()]);
  Offer::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'version'=>1,'currency'=>'GBP','salary_amount'=>1000,'status'=>'draft']);
  $this->actingAs($user,'user');
  $this->get(route('admin.talent.applications.show',$application->id))
   ->assertOk()
   ->assertSee('Synthetic Candidate')
   ->assertSee('Synthetic Engineer')
   ->assertSee('First interview')
   ->assertSee('New offer draft');
  $this->get(route('admin.talent.interviews.calendar',$interview->id))
   ->assertOk()->assertHeader('Content-Type','text/calendar; charset=utf-8')
   ->assertSee('BEGIN:VCALENDAR');
 }

 public function test_another_tenants_application_cannot_be_loaded():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $other=Tenant::create(['slug'=>'other-workspace','name'=>'Other']);
  $candidate=Candidate::create(['tenant_id'=>$other->id,'name'=>'Private Candidate','email'=>'other@example.invalid']);
  $job=Job::create(['tenant_id'=>$other->id,'slug'=>'private-role','title'=>'Private Role','description'=>'Testing']);
  $application=Application::create(['tenant_id'=>$other->id,'candidate_id'=>$candidate->id,'job_id'=>$job->id,'stage'=>'applied']);
  $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
  app(\App\Http\Controllers\Talent\ApplicationWorkspaceController::class)->show($application->id);
 }
}
