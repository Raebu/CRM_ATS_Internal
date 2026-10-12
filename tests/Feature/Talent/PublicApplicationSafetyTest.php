<?php
namespace Tests\Feature\Talent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Talent\{Job,Tenant};
use Webkul\Installer\Http\Middleware\CanInstall;
use Tests\TestCase;

class PublicApplicationSafetyTest extends TestCase
{
 use RefreshDatabase;

 public function test_application_submission_creates_candidate_and_application_in_isolated_test_database():void
 {
  $this->withoutMiddleware(CanInstall::class);
  config()->set('talent.public_applications_enabled',true);
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $job=Job::create(['tenant_id'=>$tenant->id,'title'=>'Test Applicant Vacancy','slug'=>'test-application-vacancy','description'=>'Testing','status'=>'published']);
  $this->post(route('talent.public.apply.store',$job->slug),[
   'name'=>'Synthetic Applicant','email'=>'synthetic-applicant@example.invalid','privacy_acknowledged'=>'1',
  ])->assertRedirect(route('talent.public.thank_you'));
  $this->assertDatabaseHas('talent_candidates',['tenant_id'=>$tenant->id,'email'=>'synthetic-applicant@example.invalid']);
  $this->assertDatabaseHas('talent_applications',['tenant_id'=>$tenant->id,'job_id'=>$job->id,'source'=>'raeburn_careers','stage'=>'applied']);
 }
 public function test_careers_lists_only_open_published_vacancies():void
 {
  $this->withoutMiddleware(CanInstall::class);
  config()->set('talent.public_applications_enabled',true);
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  Job::create(['tenant_id'=>$tenant->id,'title'=>'Visible Recruitment Role','slug'=>'visible-recruitment','description'=>'Open role','status'=>'published']);
  Job::create(['tenant_id'=>$tenant->id,'title'=>'Confidential Draft Role','slug'=>'confidential-draft','description'=>'Not open','status'=>'draft']);
  Job::create(['tenant_id'=>$tenant->id,'title'=>'Expired Role','slug'=>'expired-role','description'=>'Closed','status'=>'published','closes_at'=>now()->subDay()]);
  $this->get('/careers')->assertOk()->assertSee('Visible Recruitment Role')->assertDontSee('Confidential Draft Role')->assertDontSee('Expired Role');
 }
 public function test_public_application_routes_are_disabled_by_default():void
 {
  // The installer redirects all requests on an uninstalled CI instance.
  // Isolate the recruitment flag by bypassing that global installer guard.
  $this->withoutMiddleware(CanInstall::class);
  config()->set('talent.public_applications_enabled',false);
  $this->get('/careers')->assertNotFound();
  $this->get('/careers/jobs/nonexistent')->assertNotFound();
  $this->post('/careers/jobs/nonexistent/apply',[])->assertNotFound();
 }
}
