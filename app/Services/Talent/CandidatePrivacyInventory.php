<?php
namespace App\Services\Talent;

use App\Models\Talent\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Conservative, read-only erasure review inventory. No candidate identifiers
 * or free-text data are included in the report. Never performs deletion.
 */
final class CandidatePrivacyInventory
{
    public function inspect(int $tenantId, int $candidateId): array
    {
        $candidate = Candidate::where('tenant_id', $tenantId)->findOrFail($candidateId);
        $applications = DB::table('talent_applications')->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidate->id)->pluck('id')->all();
        $offers = DB::table('talent_offers')->where('tenant_id', $tenantId)
            ->whereIn('application_id', $applications)->pluck('id')->all();
        $requests = DB::table('talent_privacy_requests')->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidate->id)->pluck('id')->all();
        $counts = [
            'candidates' => 1,
            'applications' => count($applications),
            'cv_profiles' => $this->count('talent_cv_profiles', $tenantId, 'candidate_id', $candidateId),
            'documents' => $this->count('talent_documents', $tenantId, 'candidate_id', $candidateId),
            'interviews' => $this->countIn('talent_interviews', $tenantId, 'application_id', $applications),
            'offers' => count($offers),
            'offer_acceptance_challenges' => $this->countIn('talent_offer_acceptance_challenges', $tenantId, 'offer_id', $offers),
            'placements' => $this->countIn('talent_placements', $tenantId, 'application_id', $applications),
            'stage_history' => $this->countIn('talent_stage_history', $tenantId, 'application_id', $applications),
            'pool_members' => $this->count('talent_pool_members', $tenantId, 'candidate_id', $candidateId),
            'privacy_requests' => count($requests),
            'privacy_status_history' => $this->countIn('talent_privacy_status_history', $tenantId, 'privacy_request_id', $requests),
        ];
        $documentQuery = DB::table('talent_documents')->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)->select('disk', 'storage_key');
        $files = ['metadata_records' => $counts['documents'], 'existing_private_files' => 0,
            'missing_private_files' => 0, 'unsafe_or_external_references' => 0];
        foreach ($documentQuery->cursor() as $doc) {
            if ($doc->disk !== 'local' || !is_string($doc->storage_key)
                || !str_starts_with($doc->storage_key, 'talent-private/')
                || str_contains($doc->storage_key, '..')) {
                $files['unsafe_or_external_references']++;
                continue;
            }
            if (Storage::disk('local')->exists($doc->storage_key)) {
                $files['existing_private_files']++;
            } else {
                $files['missing_private_files']++;
            }
        }
        return [
            'mode' => 'review_only',
            'deletion_performed' => false,
            'counts' => $counts,
            'document_files' => $files,
            'manual_review_required' => [
                'Legal retention obligations and litigation holds',
                'Event payloads, notifications, message snapshots and mail provider records',
                'Unstructured personal data or indirect identifiers',
                'Third-party systems and backup expiry procedures',
                'Unlinked or legacy files and records outside documented relationships',
            ],
        ];
    }

    private function count(string $table, int $tenantId, string $column, int $id): int
    {
        if (!Schema::hasTable($table)) {
            throw ValidationException::withMessages(['inventory' => 'Required table missing: '.$table]);
        }
        return DB::table($table)->where('tenant_id', $tenantId)->where($column, $id)->count();
    }

    private function countIn(string $table, int $tenantId, string $column, array $ids): int
    {
        if (!Schema::hasTable($table)) {
            throw ValidationException::withMessages(['inventory' => 'Required table missing: '.$table]);
        }
        if ($ids === []) return 0;
        return DB::table($table)->where('tenant_id', $tenantId)->whereIn($column, $ids)->count();
    }
}
