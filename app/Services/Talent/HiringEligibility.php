<?php
namespace App\Services\Talent;
use App\Models\Talent\Offer;
use Illuminate\Validation\ValidationException;

final class HiringEligibility {
 public static function enforce(int $tenantId,int $applicationId):void {
  $accepted=Offer::where('tenant_id',$tenantId)->where('application_id',$applicationId)
    ->where('status','accepted')->exists();
  if (!$accepted) {
   throw ValidationException::withMessages(['stage'=>'An accepted offer is required before hiring.']);
  }
 }
}
