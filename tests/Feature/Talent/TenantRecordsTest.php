<?php

namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,Job,TalentPool,Tenant};
use App\Services\Talent\TenantRecords;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_in_another_tenant_are_not_resolvable(): void
    {
        $owner = Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
        $other = Tenant::create(['slug'=>'other-test','name'=>'Other Test Tenant']);
        $candidate = Candidate::create(['tenant_id'=>$other->id,'name'=>'Test','email'=>'other@example.invalid']);
        $job = Job::create(['tenant_id'=>$other->id,'title'=>'Test Job','slug'=>'test-job','description'=>'Test']);
        $pool = TalentPool::create(['tenant_id'=>$other->id,'name'=>'Test Pool']);

        foreach ([
            fn() => TenantRecords::candidate($owner->id,$candidate->id),
            fn() => TenantRecords::job($owner->id,$job->id),
            fn() => TenantRecords::pool($owner->id,$pool->id),
        ] as $resolve) {
            try {
                $resolve();
                $this->fail('Cross-tenant record resolved');
            } catch (ModelNotFoundException $expected) {
                $this->assertTrue(true);
            }
        }
    }
}
