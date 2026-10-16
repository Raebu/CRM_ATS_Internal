<?php

namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,PrivacyRequest,Tenant};
use App\Services\Talent\PrivacyExportAssembler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PrivacyExportAssemblerTest extends TestCase
{
 use RefreshDatabase;

 public function test_unverified_request_cannot_produce_personal_data_manifest():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Example','email'=>'example@example.invalid']);
  $request=PrivacyRequest::create([
   'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,
   'request_type'=>'access','created_by'=>1,'status'=>'pending_verification',
  ]);
  $this->expectException(ValidationException::class);
  app(PrivacyExportAssembler::class)->reviewManifest($tenant->id,$request->id);
 }

 public function test_manifest_requires_correct_tenant_and_verified_request():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $other=Tenant::create(['slug'=>'privacy-other','name'=>'Other']);
  $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Example','email'=>'example@example.invalid']);
  $request=PrivacyRequest::create([
   'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,
   'request_type'=>'access','created_by'=>1,'status'=>'identity_verified','verified_at'=>now(),
  ]);
  $manifest=app(PrivacyExportAssembler::class)->reviewManifest($tenant->id,$request->id);
  $this->assertSame('example@example.invalid',$manifest['candidate']['email']);
  $this->assertArrayHasKey('documents_manifest',$manifest);
  $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
  app(PrivacyExportAssembler::class)->reviewManifest($other->id,$request->id);
 }
}
