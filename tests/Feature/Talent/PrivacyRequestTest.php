<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,PrivacyRequest,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrivacyRequestTest extends TestCase
{
 use RefreshDatabase;
 public function test_privacy_status_history_has_actor_and_decision_columns():void
 {
  $this->assertTrue(Schema::hasTable('talent_privacy_status_history'));
  foreach(['privacy_request_id','previous_status','new_status','actor_id','resolution_code','resolution_notes','changed_at'] as $column){
   $this->assertTrue(Schema::hasColumn('talent_privacy_status_history',$column));
  }
 }

 public function test_privacy_request_is_pending_identity_verification_by_default():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Example Person','email'=>'privacy@example.invalid']);
  $request=PrivacyRequest::create([
   'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,
   'request_type'=>'access','created_by'=>1,
  ]);
  $this->assertSame('pending_verification',$request->fresh()->status);
  $this->assertNull($request->fresh()->resolved_at);
  $this->assertNull($request->fresh()->resolution_code);
 }
}