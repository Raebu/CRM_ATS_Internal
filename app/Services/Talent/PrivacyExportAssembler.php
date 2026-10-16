<?php

namespace App\Services\Talent;

use App\Models\Talent\{Application,Candidate,Document,PrivacyRequest};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Builds an internal, non-downloading review manifest only.
 * Does not copy CV files, release data to a requester, or erase any record.
 */
final class PrivacyExportAssembler
{
 public function reviewManifest(int $tenantId,int $requestId):array
 {
  $request=PrivacyRequest::where('tenant_id',$tenantId)->findOrFail($requestId);
  if ($request->request_type!=='access' || !in_array($request->status,['identity_verified','in_review'],true)
      || !$request->verified_at) {
   throw ValidationException::withMessages(['privacy_request'=>'Identity verification and an active access review are required.']);
  }
  $candidate=Candidate::where('tenant_id',$tenantId)->findOrFail($request->candidate_id);
  $applications=Application::where('tenant_id',$tenantId)
    ->where('candidate_id',$candidate->id)->orderBy('id')->get(['id','job_id','stage','source','submitted_at']);
  $documents=Document::where('tenant_id',$tenantId)
    ->where('candidate_id',$candidate->id)->orderBy('id')->get(['id','original_name','scan_status','size_bytes']);
  return [
    'request_id'=>$request->id,
    'candidate'=>['id'=>$candidate->id,'name'=>$candidate->name,'email'=>$candidate->email,'phone'=>$candidate->phone],
    'applications'=>$applications->toArray(),
    'documents_manifest'=>$documents->toArray(),
    'note'=>'Internal review manifest only: assess third-party data, legal exemptions and completeness before authorised disclosure.',
  ];
 }
}
