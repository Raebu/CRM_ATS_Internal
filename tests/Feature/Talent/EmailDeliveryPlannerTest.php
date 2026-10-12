<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Tenant,RecruitmentNotification,EmailDelivery};
use App\Services\Talent\EmailDeliveryPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class EmailDeliveryPlannerTest extends TestCase
{
 use RefreshDatabase;

 public function test_draft_creation_is_idempotent_and_does_not_send():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $notice=RecruitmentNotification::create([
   'tenant_id'=>$tenant->id,'source_event_id'=>(string)Str::uuid(),
   'kind'=>'new_application','status'=>'pending'
  ]);
  $planner=app(EmailDeliveryPlanner::class);
  $first=$planner->draft($tenant->id,$notice->id,'application_acknowledgement');
  $second=$planner->draft($tenant->id,$notice->id,'application_acknowledgement');
  $this->assertSame($first->id,$second->id);
  $this->assertSame('draft',$first->fresh()->status);
  $this->assertNull($first->fresh()->sent_at);
  $this->assertSame(1,EmailDelivery::count());
 }

 public function test_cannot_draft_for_notification_belonging_to_another_tenant():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $other=Tenant::create(['slug'=>'planner-other','name'=>'Other']);
  $notice=RecruitmentNotification::create([
   'tenant_id'=>$other->id,'source_event_id'=>(string)Str::uuid(),
   'kind'=>'new_application','status'=>'pending'
  ]);
  $this->expectException(ModelNotFoundException::class);
  app(EmailDeliveryPlanner::class)->draft($tenant->id,$notice->id,'application_acknowledgement');
 }
}
