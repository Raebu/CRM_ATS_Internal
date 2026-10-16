<?php
namespace App\Services\Talent;

use App\Models\Talent\DomainEvent;
use App\Services\Talent\NotificationProjector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Claims and publishes durable events. Delivers Laravel events at least once.
 * Consumers MUST deduplicate using event_id. Never embed candidate PII in payloads.
 */
class OutboxDispatcher
{
 public function dispatchBatch(int $batchSize = 25): array
 {
  $succeeded=0;$failed=0;
  for($i=0;$i<$batchSize;$i++){
   $event=DB::transaction(function(){
    $event=DomainEvent::whereNull('published_at')->whereNull('dead_lettered_at')
     ->where(fn($q)=>$q->whereNull('next_attempt_at')->orWhere('next_attempt_at','<=',now()))
     ->where(fn($q)=>$q->whereNull('claimed_until')->orWhere('claimed_until','<=',now()))
     ->orderBy('id')->lockForUpdate()->first();
    if(!$event)return null;
    $event->update(['claimed_until'=>now()->addMinutes(2)]);
    return $event;
   });
   if(!$event)break;
   try {
    // Persist the internal notification projection before publishing the event.
    app(NotificationProjector::class)->project($event->event_id,$event->event_type,$event->tenant_id,$event->payload);
    // Laravel listeners may run synchronously or be queued. Delivery is at-least-once.
    event('talent.domain_event', [$event->event_id,$event->event_type,$event->tenant_id,$event->payload]);
    DomainEvent::whereKey($event->id)->whereNull('published_at')
     ->update(['published_at'=>now(),'claimed_until'=>null,'last_error'=>null]);
    $succeeded++;
   }catch(Throwable $e){
    DB::transaction(function()use($event,$e){
     $locked=DomainEvent::whereKey($event->id)->lockForUpdate()->firstOrFail();
     $attempts=$locked->attempts+1;
     $locked->update([
      'attempts'=>$attempts,'claimed_until'=>null,
      'next_attempt_at'=>$attempts>=5?null:now()->addSeconds(min(3600,30*(2**($attempts-1)))),
      'dead_lettered_at'=>$attempts>=5?now():null,
      'last_error'=>mb_substr($e->getMessage(),0,1000)
     ]);
    });
    Log::warning('Talent outbox event delivery failed',['event_id'=>$event->event_id,'exception_class'=>get_class($e)]);
    $failed++;
   }
  }
  return ['published'=>$succeeded,'failed'=>$failed];
 }
}
