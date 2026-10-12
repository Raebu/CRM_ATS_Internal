<?php

namespace Tests\Unit\Talent;

use App\Http\Middleware\TalentAuthorization;
use Tests\TestCase;

class TalentAuthorizationTest extends TestCase
{
    public function test_all_access_role_is_permitted(): void
    {
        $this->assertTrue(TalentAuthorization::hasRolePermission('all', [], 'talent'));
    }

    public function test_recruiter_without_sensitive_permission_is_denied(): void
    {
        $this->assertFalse(TalentAuthorization::hasRolePermission('custom', ['talent'], 'talent.offers.edit'));
        $this->assertFalse(TalentAuthorization::hasRolePermission('custom', [], 'talent'));
    }

    public function test_specific_role_permission_is_required(): void
    {
        $this->assertTrue(TalentAuthorization::hasRolePermission('custom', ['talent.candidates.create'], 'talent.candidates.create'));
        $this->assertFalse(TalentAuthorization::hasRolePermission('custom', ['talent.candidates.create'], 'talent.applications.edit'));
    }
}
