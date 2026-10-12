<?php
namespace App\Services\Talent;

use App\Models\Talent\Offer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Preparatory challenge service. No external endpoints, emailing or offer acceptance. */
final class OfferAcceptanceChallenge
{
 public function validateChallenge(int $tenantId,int $offerId,string $token):bool
 {
  if (!config('talent.verified_offer_acceptance_enabled',false) || !preg_match('/^[A-Za-z0-9]{64}$/D',$token)) {
   return false;
  }
  $offer=Offer::where('tenant_id',$tenantId)->whereKey($offerId)->first();
  if (!$offer || $offer->status!=='sent') return false;
  return DB::table('talent_offer_acceptance_challenges')
   ->where('tenant_id',$tenantId)->where('offer_id',$offerId)
   ->where('token_hash',hash('sha256',$token))
   ->whereNull('consumed_at')->whereNull('revoked_at')
   ->where('expires_at','>',now())->exists();
 }

 public function issue(int $tenantId,int $offerId,?int $actorId=null):array
 {
  return DB::transaction(function()use($tenantId,$offerId,$actorId){
   $offer=Offer::where('tenant_id',$tenantId)->lockForUpdate()->findOrFail($offerId);
   if($offer->status!=='sent' || !config('talent.verified_offer_acceptance_enabled',false)){
    throw ValidationException::withMessages(['offer'=>'Verified offer acceptance is disabled or the offer was not delivered.']);
   }
   DB::table('talent_offer_acceptance_challenges')
    ->where('tenant_id',$tenantId)->where('offer_id',$offerId)->whereNull('consumed_at')
    ->update(['revoked_at'=>now()]);
   $token=Str::random(64);
   DB::table('talent_offer_acceptance_challenges')->insert([
    'tenant_id'=>$tenantId,'offer_id'=>$offerId,
    'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addHours(24),
    'issued_by'=>$actorId,'created_at'=>now(),'updated_at'=>now(),
   ]);
   return ['token'=>$token,'expires_at'=>now()->addHours(24)];
  });
 }
}
