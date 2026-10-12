<?php
namespace App\Console\Commands;

use App\Models\Talent\Interview;
use App\Models\Talent\RecruitmentNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTalentInterviewReminders extends Command
{
 protected $signature='talent:interview-reminders {--hours=24}';
 protected $description='Create deduplicated internal reminders for upcoming interviews (no email sent)';

 public function handle():int
 {
  $hours=max(1,min(168,(int)$this->option('hours')));
  $created=0;
  Interview::query()->where('status','scheduled')
   ->where('starts_at','>',now())->where('starts_at','<=',now()->addHours($hours))
   ->orderBy('id')->chunkById(100,function($interviews)use(&$created){
    foreach($interviews as $interview){
     // Stable UUID based on interview ID and start time makes repeated runs idempotent.
     $eventId=Str::uuid5(
      \Ramsey\Uuid\Uuid::NAMESPACE_DNS,
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
