<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{RecruitmentNotification,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
 use RefreshDatabase;

 public function test_notifications_belong_to_a_tenant_and_begin_pending():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $notification=RecruitmentNotification::create([
   'tenant_id'=>$tenant->id,'source_event_id'=>(string)Str::uuid(),
   'kind'=>'new_application','status'=>'pending',
  ]);
  $this->assertSame($tenant->id,$notification->fresh()->tenant_id);
  $this->assertSame('pending',$notification->fresh()->status);
  $this->assertNull($notification->fresh()->acknowledged_at);
 }
}
