<?php
namespace App\Services\Talent;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ErasureReviewApproval
{
    public const CATEGORIES = [
        'candidate_identity','applications','cv_profiles','documents',
        'interviews','offers','offer_challenges','placements',
        'stage_history','pool_members','privacy_history',
        'events_notifications_email','external_processors','backups',
    ];

    /** Approval records a review decision only; it never enables deletion. */
    public function approve(int $tenantId, int $reviewId, int $actorId): void
    {
        DB::transaction(function () use ($tenantId, $reviewId, $actorId): void {
            $review = DB::table('talent_erasure_reviews')
                ->where('tenant_id', $tenantId)->where('id', $reviewId)
                ->lockForUpdate()->first();
            if (!$review || $review->status !== 'draft' || (int) $review->prepared_by === $actorId) {
                $this->reject();
            }
            $request = DB::table('talent_privacy_requests')
                ->where('tenant_id', $tenantId)
                ->where('id', $review->privacy_request_id)
                ->where('candidate_id', $review->candidate_id)
                ->first();
            if (!$request || $request->request_type !== 'erasure'
                || $request->status !== 'in_review'
                || !$request->verified_at || !$request->identity_evidence_reviewed_at) {
                $this->reject();
            }
            if (!preg_match('/^[a-f0-9]{64}$/i', (string) $review->inventory_digest)
                || trim((string) $review->legal_hold_notes) === ''
                || trim((string) $review->backup_handling_notes) === '') {
                $this->reject();
            }
            $currentDigest = app(CandidateErasureFingerprint::class)->digest($tenantId, (int)$review->candidate_id);
            if (!hash_equals((string) $review->inventory_digest, $currentDigest)) {
                $this->reject();
            }
            $decisions = DB::table('talent_erasure_decisions')
                ->where('tenant_id', $tenantId)->where('review_id', $reviewId)->get();
            if ($decisions->count() !== count(self::CATEGORIES)
                || $decisions->pluck('data_category')->sort()->values()->all() !==
                    collect(self::CATEGORIES)->sort()->values()->all()) {
                $this->reject();
            }
            foreach ($decisions as $decision) {
                if (!in_array($decision->proposed_action, ['erase','retain'], true)
                    || trim((string) $decision->rationale) === ''
                    || !$decision->reviewed_at || !$decision->reviewed_by
                    || ($decision->proposed_action === 'retain'
                        && trim((string) $decision->legal_basis) === '')) {
                    $this->reject();
                }
            }
            // Audit only pseudonymous IDs and the digest, never candidate personal data.
            DB::table('talent_events')->insert([
                'tenant_id'=>$tenantId,
                'event_id'=>(string)\Illuminate\Support\Str::uuid(),
                'event_type'=>'privacy.erasure_review_approved.v1',
                'event_version'=>1,
                'aggregate_type'=>'erasure_review',
                'aggregate_id'=>$reviewId,
                'payload'=>json_encode([
                    'review_id'=>$reviewId,
                    'privacy_request_id'=>$review->privacy_request_id,
                    'prepared_by'=>$review->prepared_by,
                    'approved_by'=>$actorId,
                    'fingerprint_version'=>CandidateErasureFingerprint::VERSION,
                    'inventory_digest'=>$review->inventory_digest,
                    'deletion_performed'=>false,
                ],JSON_THROW_ON_ERROR),
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('talent_erasure_reviews')->where('id', $reviewId)
                ->where('tenant_id', $tenantId)
                ->update(['status'=>'review_approved','approved_by'=>$actorId,
                    'approved_at'=>now(),'updated_at'=>now()]);
        });
    }

    private function reject(): never
    {
        throw ValidationException::withMessages([
            'erasure_review'=>'Review cannot be approved: independent approval, identity, inventory and category/legal checks must be complete.',
        ]);
    }
}
