<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Tenant,RecruitmentNotification,EmailDelivery};
use App\Services\Talent\EmailDeliveryPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

class RecruitmentEmailSubmissionTest extends TestCase {
 use RefreshDatabase;
 public function test_approved_email_can_be_submitted_once_to_fake_provider():void {
  $this->withoutMiddleware(CanInstall::class);
  $role=Role::create(['name'=>'Recruitment sender','permission_type'=>'all','permissions'=>[]]);
  $user=User::create(['name'=>'Email Test','email'=>'email-test-'.uniqid().'@example.invalid','password'=>bcrypt('secret'),'role_id'=>$role->id,'status'=>1]);
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $notice=RecruitmentNotification::create(['tenant_id'=>$tenant->id,'source_event_id'=>(string)Str::uuid(),'kind'=>'new_application','status'=>'pending']);
  $delivery=app(EmailDeliveryPlanner::class)->draft($tenant->id,$notice->id,'application_acknowledgement');
  $delivery->update(['status'=>'approved','approved_by'=>$user->id,'approved_at'=>now(),'recipient_email'=>'synthetic@example.invalid']);
  config()->set('talent.external_email_enabled',true);
  config()->set('talent.resend_api_key','fake-key');
  config()->set('talent.resend_from','recruitment@example.invalid');
  Http::fake(['api.resend.com/emails'=>Http::response(['id'=>'provider-fixture-1'],200)]);
  $this->actingAs($user,'user');
  $this->post(route('admin.talent.email.submit',$delivery->id))->assertRedirect();
  $this->assertDatabaseHas('talent_email_deliveries',['id'=>$delivery->id,'status'=>'submitted','provider_message_id'=>'provider-fixture-1']);
  $this->post(route('admin.talent.email.submit',$delivery->id))->assertStatus(409);
  Http::assertSentCount(1);
 }
}
