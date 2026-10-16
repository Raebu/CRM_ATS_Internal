<?php
namespace App\Services\Talent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Versioned HMAC of directly linked candidate data and private document bytes.
 * Does not claim to discover unlinked PII, vendor copies, emails or backups.
 */
final class CandidateErasureFingerprint
{
    public const VERSION = 'candidate-records-v2';

    public function digest(int $tenantId, int $candidateId): string
    {
        $configured = config('app.key');
        $key = is_string($configured) && str_starts_with($configured, 'base64:')
            ? base64_decode(substr($configured, 7), true) : $configured;
        if (!is_string($key) || strlen($key) < 32) {
            throw ValidationException::withMessages(['inventory'=>'Secure fingerprint key unavailable.']);
        }
        $context = hash_init('sha256', HASH_HMAC, $key);
        hash_update($context, self::VERSION."\n".$tenantId."\n".$candidateId."\n");

        $candidates = $this->rows('talent_candidates',$tenantId,'candidate_id',[$candidateId],true);
        if (count($candidates) !== 1) abort(404);
        $applications = $this->rows('talent_applications',$tenantId,'candidate_id',[$candidateId]);
        $applicationIds = array_column($applications,'id');
        $offers = $this->rows('talent_offers',$tenantId,'application_id',$applicationIds);
        $offerIds = array_column($offers,'id');
        $requests = $this->rows('talent_privacy_requests',$tenantId,'candidate_id',[$candidateId]);
        $requestIds = array_column($requests,'id');

        $sets = [
            'candidates'=>$candidates,
            'applications'=>$applications,
            'cv_profiles'=>$this->rows('talent_cv_profiles',$tenantId,'candidate_id',[$candidateId]),
            'documents'=>$this->rows('talent_documents',$tenantId,'candidate_id',[$candidateId]),
            'interviews'=>$this->rows('talent_interviews',$tenantId,'application_id',$applicationIds),
            'offers'=>$offers,
            'offer_challenges'=>$this->rows('talent_offer_acceptance_challenges',$tenantId,'offer_id',$offerIds),
            'placements'=>$this->rows('talent_placements',$tenantId,'application_id',$applicationIds),
            'stage_history'=>$this->rows('talent_stage_history',$tenantId,'application_id',$applicationIds),
            'pool_members'=>$this->rows('talent_pool_members',$tenantId,'candidate_id',[$candidateId]),
            'privacy_requests'=>$requests,
            'privacy_history'=>$this->rows('talent_privacy_status_history',$tenantId,'privacy_request_id',$requestIds),
        ];
        foreach ($sets as $name=>$rows) {
            hash_update($context, json_encode([$name,$rows],JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE));
        }
        foreach ($sets['documents'] as $document) {
            $path = $document['storage_key'] ?? null;
            if (($document['disk'] ?? null) !== 'local' || !is_string($path)
                || !str_starts_with($path,'talent-private/') || str_contains($path,'..')) {
                throw ValidationException::withMessages(['inventory'=>'Unsafe document reference requires manual resolution.']);
            }
            $stream=Storage::disk('local')->readStream($path);
            if (!is_resource($stream)) {
                throw ValidationException::withMessages(['inventory'=>'Missing document prevents approval.']);
            }
            try {
                $fileHash=hash_init('sha256');
                if (hash_update_stream($fileHash,$stream) === false) {
                    throw ValidationException::withMessages(['inventory'=>'Cannot fingerprint document.']);
                }
                hash_update($context, (string)$document['id'].':'.hash_final($fileHash));
            } finally {
                fclose($stream);
            }
        }
        return hash_final($context);
    }

    private function rows(string $table,int $tenantId,string $relation,array $ids,bool $candidateTable=false): array
    {
        if (!Schema::hasTable($table)) {
            throw ValidationException::withMessages(['inventory'=>'Required table missing: '.$table]);
        }
        if (!$ids) return [];
        $query=DB::table($table)->where('tenant_id',$tenantId);
        $query->whereIn($candidateTable ? 'id' : $relation,$ids);
        return $query->orderBy('id')->get()->map(static fn ($row)=>(array)$row)->all();
    }
}
