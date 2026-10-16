<?php
namespace App\Services\Talent;

use Illuminate\Support\Facades\DB;

/**
 * Fail-closed, read-only candidate erasure plan. No erase operation is exposed.
 * Indirect free-text and third-party records require human investigation.
 */
final class CandidateErasurePlan
{
    public function build(int $tenantId, int $candidateId): array
    {
        $inventory = app(CandidatePrivacyInventory::class)->inspect($tenantId, $candidateId);
        $counts = $inventory['counts'];
        $hasRetentionReview = $counts['offers'] > 0 || $counts['placements'] > 0;
        $references = [
            'talent_offer_acceptance_challenges',
            'talent_offers',
            'talent_placements',
            'talent_interviews',
            'talent_stage_history',
            'talent_cv_profiles',
            'talent_documents',
            'talent_pool_members',
            'talent_applications',
            'talent_privacy_status_history',
            'talent_privacy_requests',
            'talent_candidates',
        ];
        return [
            'mode' => 'dry_run_only',
            'can_execute' => false,
            'deletion_performed' => false,
            'counts' => $counts,
            'document_files' => $inventory['document_files'],
            'dependency_review_order' => $references,
            'blockers' => array_values(array_filter([
                'Identity verification and authority to erase must be established.',
                $hasRetentionReview ? 'Offers or placements require contractual/legal retention review.' : null,
                'Review all event payloads, notifications, email deliveries and free-text data.',
                'Review lawful retention, litigation holds and third-party disclosures.',
                'Specify backup retention/expiry and restoration suppression procedures.',
                'Implement transaction-safe erasure and independently verify results.',
            ])),
        ];
    }
}
