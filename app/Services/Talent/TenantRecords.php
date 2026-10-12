<?php

namespace App\Services\Talent;

use App\Models\Talent\{Application,Candidate,Job,TalentPool};
use Illuminate\Validation\ValidationException;

/**
 * Resolve recruitment relations only inside the active tenant.
 * Controllers must never trust a client-supplied tenant ID.
 */
final class TenantRecords
{
    public static function job(int $tenantId, int $jobId): Job
    {
        return Job::query()->where('tenant_id', $tenantId)->findOrFail($jobId);
    }

    public static function candidate(int $tenantId, int $candidateId): Candidate
    {
        return Candidate::query()->where('tenant_id', $tenantId)->findOrFail($candidateId);
    }

    public static function application(int $tenantId, int $applicationId): Application
    {
        return Application::query()->where('tenant_id', $tenantId)->findOrFail($applicationId);
    }

    public static function pool(int $tenantId, int $poolId): TalentPool
    {
        return TalentPool::query()->where('tenant_id', $tenantId)->findOrFail($poolId);
    }

    public static function requireApplicationRelations(int $tenantId, int $jobId, int $candidateId): void
    {
        self::job($tenantId, $jobId);
        self::candidate($tenantId, $candidateId);
    }
}
