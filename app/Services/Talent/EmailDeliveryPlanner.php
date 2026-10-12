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
  EmailTemplateCatalog::get($templateKey);
  return DB::transaction(function()use($tenantId,$notificationId,$templateKey){
   RecruitmentNotification::where('tenant_id',$tenantId)->findOrFail($notificationId);
   return EmailDelivery::firstOrCreate(
    ['notification_id'=>$notificationId,'template_key'=>$templateKey],
    ['tenant_id'=>$tenantId,'status'=>'draft']
   );
  });
 }
}
