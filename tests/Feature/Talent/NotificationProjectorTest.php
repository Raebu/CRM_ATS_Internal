<?php
namespace Tests\Feature\Talent;
use App\Models\Talent\{RecruitmentNotification,Tenant};
use App\Services\Talent\NotificationProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class NotificationProjectorTest extends TestCase {
 use RefreshDatabase;
 public function test_replayed_event_creates_only_one_notification():void {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $id=(string)Str::uuid();
  $projector=app(NotificationProjector::class);
  $projector->project($id,'application.created.v1',$tenant->id,['application_id'=>15]);
  $projector->project($id,'application.created.v1',$tenant->id,['application_id'=>15]);
  $this->assertSame(1,RecruitmentNotification::where('source_event_id',$id)->count());
 }
 public function test_unrecognised_events_are_ignored():void {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  app(NotificationProjector::class)->project((string)Str::uuid(),'unrecognised.v1',$tenant->id,[]);
  $this->assertSame(0,RecruitmentNotification::count());
 }
}
