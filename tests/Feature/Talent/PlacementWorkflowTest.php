<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Job,Offer,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

class PlacementWorkflowTest extends TestCase {
 use RefreshDatabase;
 private function setupApplication(string $stage):array {
  $this->withoutMiddleware(CanInstall::class);
  $role=Role::create(['name'=>'Placement operator','permission_type'=>'all','permissions'=>[]]);
  $user=User::create(['name'=>'Placement Operator','email'=>'operator-'.uniqid().'@example.invalid','password'=>bcrypt('secret'),'role_id'=>$role->id,'status'=>1]);
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Hire','email'=>'hire@example.invalid']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Synthetic Placement','slug'=>'synthetic-placement','description'=>'Test role']);
  $application=Application::create(['tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,'stage'=>$stage]);
  $this->actingAs($user,'user');
  return [$application,$user,$tenant];
 }
 public function test_only_hired_applicants_can_create_placements():void {
  [$application]=$this->setupApplication('offer');
  $this->post(route('admin.talent.placements.store',$application->id),['start_date'=>now()->addDays(10)->toDateString()])
   ->assertSessionHasErrors('application');
  $this->assertDatabaseCount('talent_placements',0);
 }
 public function test_hired_application_creates_five_checklist_tasks_and_tracks_completion():void {
  [$application,$user,$tenant]=$this->setupApplication('hired');
  $this->post(route('admin.talent.placements.store',$application->id),['start_date'=>now()->addDays(10)->toDateString(),'handover_notes'=>'Internal handover'])->assertRedirect();
  $placement=\Illuminate\Support\Facades\DB::table('talent_placements')->where('application_id',$application->id)->first();
  $this->assertNotNull($placement);
  $this->assertSame($user->id,(int)$placement->recruiter_id);
  $this->assertDatabaseCount('talent_placement_tasks',5);
  $task=\Illuminate\Support\Facades\DB::table('talent_placement_tasks')->where('placement_id',$placement->id)->first();
  $this->patch(route('admin.talent.placements.tasks.update',[$placement->id,$task->id]),['completed'=>1])->assertRedirect();
  $this->assertNotNull(\Illuminate\Support\Facades\DB::table('talent_placement_tasks')->where('id',$task->id)->value('completed_at'));
  $this->get(route('admin.talent.applications.show',$application->id))->assertOk()->assertSee('Onboarding checklist');
  $this->post(route('admin.talent.placements.store',$application->id),['start_date'=>now()->addDays(10)->toDateString()])->assertSessionHasErrors('application');
 }
}
