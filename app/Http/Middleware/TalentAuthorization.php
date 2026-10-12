<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Additional fail-closed protection for sensitive recruitment records.
 * Krayin's menu ACL alone must not be the only access control.
 */
class TalentAuthorization
{
    private const PERMISSIONS = [
        'admin.talent.index' => 'talent',
        'admin.talent.jobs.store' => 'talent.jobs.create',
        'admin.talent.jobs.update' => 'talent.jobs.edit',
        'admin.talent.candidates.store' => 'talent.candidates.create',
        'admin.talent.applications.store' => 'talent.applications.create',
        'admin.talent.applications.update' => 'talent.applications.edit',
        'admin.talent.interviews.store' => 'talent.interviews.create',
        'admin.talent.interviews.update' => 'talent.interviews.edit',
        'admin.talent.offers.store' => 'talent.offers.create',
        'admin.talent.offers.update' => 'talent.offers.edit',
        'admin.talent.pools.store' => 'talent.pools.create',
        'admin.talent.pools.candidates.store' => 'talent.pools.manage',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->guard('user')->user();

        if (! $user || ! $user->status || ! $user->role) {
            abort(403);
        }

        $permission = self::PERMISSIONS[$request->route()?->getName() ?? ''] ?? null;

        if (! $permission || ! self::hasRolePermission($user->role->permission_type, $user->role->permissions ?? [], $permission)) {
            abort(403);
        }

        return $next($request);
    }

    public static function hasRolePermission(string $type, ?array $permissions, string $required): bool
    {
        if ($type === 'all') {
            return true;
        }

        return $type === 'custom' && in_array($required, $permissions ?? [], true);
    }
}
