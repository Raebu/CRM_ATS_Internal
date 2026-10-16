<?php
namespace App\Services\Talent;

use App\Models\Talent\EmailDelivery;
use App\Models\Talent\RecruitmentNotification;
use Illuminate\Support\Facades\DB;

/** Prepare a delivery for human review; never sends messages. */
class EmailDeliveryPlanner
{
 public function draft(int $tenantId,int $notificationId,string $templateKey):EmailDelivery
 {
  $template=EmailTemplateCatalog::get($templateKey);
  return DB::transaction(function()use($tenantId,$notificationId,$templateKey,$template){
   RecruitmentNotification::where('tenant_id',$tenantId)->findOrFail($notificationId);
   return EmailDelivery::firstOrCreate(
    ['notification_id'=>$notificationId,'template_key'=>$templateKey],
    ['tenant_id'=>$tenantId,'status'=>'draft',
     'template_version'=>$template['version'],
     'template_checksum'=>hash('sha256',$template['subject']."\n".$template['body']),
     'subject_snapshot'=>$template['subject'],'body_snapshot'=>$template['body']]
   );
  });
 }
}
