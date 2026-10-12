<?php
namespace App\Console\Commands;

use App\Models\Talent\Interview;
use App\Models\Talent\RecruitmentNotification;
use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;

class CreateTalentInterviewReminders extends Command
{
 protected $signature='talent:interview-reminders {--hours=24}';
 protected $description='Create deduplicated internal reminders for upcoming interviews (no email sent)';

 public function handle():int
 {
  $hours=max(1,min(168,(int)$this->option('hours')));
  $created=0;
  // Invalidate pending reminders when the interview was cancelled, completed,
  // moved, or removed. Old reminders must not stay actionable.
  RecruitmentNotification::query()->where('kind','interview_reminder')
   ->where('status','pending')->orderBy('id')->chunkById(100,function($reminders){
    foreach($reminders as $reminder){
     $data=$reminder->data ?? [];
     $interview=Interview::query()->where('tenant_id',$reminder->tenant_id)
      ->find($data['interview_id'] ?? null);
     if(!$interview || $interview->status!=='scheduled'
       || $interview->starts_at?->toIso8601String() !== ($data['starts_at'] ?? null)
       || $interview->starts_at <= now()){
      $reminder->update(['status'=>'cancelled']);
     }
    }
   });
  Interview::query()->where('status','scheduled')
   ->where('starts_at','>',now())->where('starts_at','<=',now()->addHours($hours))
   ->orderBy('id')->chunkById(100,function($interviews)use(&$created){
    foreach($interviews as $interview){
     // Stable UUID based on interview ID and start time makes repeated runs idempotent.
     $eventId=Uuid::uuid5(
      Uuid::NAMESPACE_DNS,
      'talent-interview-reminder-'.$interview->id.'-'.$interview->starts_at->toIso8601String()
     )->toString();
     $record=RecruitmentNotification::firstOrCreate(
      ['source_event_id'=>$eventId,'kind'=>'interview_reminder'],
      ['tenant_id'=>$interview->tenant_id,'status'=>'pending','data'=>[
       'interview_id'=>$interview->id,
       'starts_at'=>$interview->starts_at->toIso8601String(),
      ]]
     );
     if($record->wasRecentlyCreated)$created++;
    }
   });
  $this->info("Created {$created} new internal interview reminders.");
  return self::SUCCESS;
 }
}
