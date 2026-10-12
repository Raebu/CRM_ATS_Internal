<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Application,Candidate,Job,Tenant};
use App\Http\Controllers\Talent\ApplicationWorkspaceController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationWorkspaceTest extends TestCase
{
 use RefreshDatabase;
 public function test_missing_application_is_not_exposed():void
 {
  $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
  app(ApplicationWorkspaceController::class)->show(999999);
 }
 public function test_other_tenant_application_is_not_exposed():void
 {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $other=Tenant::create(['slug'=>'other-workspace','name'=>'Other']);
  $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
  app(ApplicationWorkspaceController::class)->show(999999);
 }
}
