<?php
namespace App\Services\Talent;

use App\Models\Talent\RecruitmentNotification;

/**
 * Duplicate-safe internal notification projection.
 * No external email or candidate contact is sent.
 */
class NotificationProjector
{
 private const TYPES=[
  'application.created.v1'=>'new_application',
  'interview.scheduled.v1'=>'interview_scheduled',
  'privacy.request_created.v1'=>'privacy_request',
  'distribution.failed.v1'=>'distribution_failure',
 ];

 public function project(string $eventId,string $eventType,int $tenantId,array $payload):void
 {
  $kind=self::TYPES[$eventType]??null;
  if(!$kind)return;
  RecruitmentNotification::firstOrCreate(
   ['source_event_id'=>$eventId,'kind'=>$kind],
   ['tenant_id'=>$tenantId,'status'=>'pending','data'=>[
    'record_id'=>$payload['application_id']??$payload['job_id']??null,
    'candidate_id'=>$payload['candidate_id']??null,
   ]]
  );
 }
}
