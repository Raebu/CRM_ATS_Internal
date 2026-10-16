<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,TalentPool,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentPoolTest extends TestCase
{
 use RefreshDatabase;

 public function test_pool_membership_can_be_assigned_to_candidate():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Test Candidate','email'=>'candidate@example.invalid']);
  $pool=TalentPool::create(['tenant_id'=>$tenant->id,'name'=>'Engineering']);
  $pool->candidates()->attach($candidate->id,['tenant_id'=>$tenant->id,'added_at'=>now()]);
  $this->assertEquals([$candidate->id],$pool->candidates()->pluck('talent_candidates.id')->all());
 }
}
