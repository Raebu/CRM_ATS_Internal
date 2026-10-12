<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{DomainEvent,Tenant};
use App\Services\Talent\OutboxDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutboxDispatcherTest extends TestCase
{
 use RefreshDatabase;
 public function test_pending_event_publishes_once_when_dispatcher_repeats():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $event=DomainEvent::create(['tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),
   'event_type'=>'job.created.v1','event_version'=>1,'aggregate_type'=>'job','aggregate_id'=>123,'payload'=>[]]);
  Event::fake(['talent.domain_event']);
  $dispatcher=app(OutboxDispatcher::class);
  $this->assertSame(1,$dispatcher->dispatchBatch()['published']);
  $this->assertSame(0,$dispatcher->dispatchBatch()['published']);
  $this->assertNotNull($event->fresh()->published_at);
 }
}
