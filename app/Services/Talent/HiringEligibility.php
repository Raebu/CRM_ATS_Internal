<?php
namespace App\Services\Talent;
use App\Models\Talent\Offer;
use Illuminate\Validation\ValidationException;

final class HiringEligibility {
 public static function enforce(int $tenantId,int $applicationId):void {
  $latest=Offer::where('tenant_id',$tenantId)->where('application_id',$applicationId)
    ->orderByDesc('version')->orderByDesc('id')->first();
  $accepted=$latest && $latest->status==='accepted';
  if (!$accepted) {
   throw ValidationException::withMessages(['stage'=>'An accepted offer is required before hiring.']);
  }
 }
}
