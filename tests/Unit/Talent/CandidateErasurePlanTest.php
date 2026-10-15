<?php
namespace Tests\Unit\Talent;
use App\Services\Talent\CandidateErasurePlan;
use App\Services\Talent\CandidatePrivacyInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Talent\{Tenant,Candidate};
class CandidateErasurePlanTest extends TestCase {
 use RefreshDatabase;
 public function test_plan_does_not_erase_records(): void {
  $tenant=Tenant::create(['slug'=>'dry-run-tenant','name'=>'Dry Run Tenant']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Test','email'=>'synthetic@example.invalid']);
  $plan=app(CandidateErasurePlan::class)->build($tenant->id,$candidate->id);
  $this->assertSame('dry_run_only',$plan['mode']);
  $this->assertFalse($plan['can_execute']);
  $this->assertFalse($plan['deletion_performed']);
  $this->assertNotEmpty($plan['blockers']);
  $this->assertTrue(Candidate::whereKey($candidate->id)->exists());
 }
}
