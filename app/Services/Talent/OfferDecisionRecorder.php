<?php
namespace App\Services\Talent;

use App\Models\Talent\{DomainEvent,Offer};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Records a candidate decision only after a separately authenticated caller
 * has established candidate identity and possession of the issued challenge.
 * This service does not authenticate users or expose a public endpoint.
 */
final class OfferDecisionRecorder
{
 public function record(int $tenantId,int $offerId,string $token,string $decision,int $verifiedCandidateId):bool
 {
  if (!config('talent.verified_offer_acceptance_enabled',false)
      || !in_array($decision,['accepted','declined'],true)
      || !preg_match('/^[A-Za-z0-9]{64}$/D',$token)) return false;
  return DB::transaction(function()use($tenantId,$offerId,$token,$decision,$verifiedCandidateId){
   $offer=Offer::where('tenant_id',$tenantId)->lockForUpdate()->find($offerId);
   if(!$offer || $offer->status!=='sent')return false;
   $application=$offer->application()->where('tenant_id',$tenantId)->first();
   if(!$application || (int)$application->candidate_id!==$verifiedCandidateId)return false;
   $challenge=DB::table('talent_offer_acceptance_challenges')
    ->where('tenant_id',$tenantId)->where('offer_id',$offerId)
    ->where('token_hash',hash('sha256',$token))
    ->whereNull('consumed_at')->whereNull('revoked_at')
    ->where('expires_at','>',now())->lockForUpdate()->first();
   if(!$challenge)return false;
   $changed=DB::table('talent_offer_acceptance_challenges')->where('id',$challenge->id)
    ->whereNull('consumed_at')->whereNull('revoked_at')->where('expires_at','>',now())
    ->update(['consumed_at'=>now(),'updated_at'=>now()]);
   if($changed!==1)return false;
   $offer->update(['status'=>$decision,'accepted_at'=>$decision==='accepted'?now():null]);
   DB::table('talent_offer_challenge_audit')->insert([
    'tenant_id'=>$tenantId,'offer_id'=>$offerId,'challenge_id'=>$challenge->id,
    'action'=>$decision,'created_at'=>now(),
   ]);
   DomainEvent::create(['tenant_id'=>$tenantId,'event_id'=>(string)Str::uuid(),
    'event_type'=>'offer.candidate_decision.v1','event_version'=>1,
    'aggregate_type'=>'offer','aggregate_id'=>$offerId,
    'payload'=>['application_id'=>$application->id,'decision'=>$decision],
   ]);
   return true;
  });
 }
}
