<?php
namespace Tests\Feature\Talent;

use App\Models\Talent\{Candidate,PrivacyRequest,Tenant};
use App\Services\Talent\CandidateErasureFingerprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

final class ErasureReviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_privacy_permissions_cannot_view_or_create_review(): void
    {
        $this->withoutMiddleware(CanInstall::class);
        $role = Role::create(['name'=>'No Privacy Access','permission_type'=>'custom','permissions'=>['talent.candidates.view']]);
        $user = User::create([
            'name'=>'Restricted Synthetic User','email'=>'restricted-'.uniqid().'@example.invalid',
            'password'=>bcrypt('synthetic-only'),'status'=>1,'role_id'=>$role->id,
        ]);
        $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
        $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic Candidate','email'=>'restricted-candidate@example.invalid']);
        $request=PrivacyRequest::create([
            'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,'request_type'=>'erasure',
            'status'=>'in_review','created_by'=>$user->id,
            'verified_at'=>now(),'identity_evidence_reviewed_at'=>now(),
        ]);
        $this->actingAs($user,'user');
        $this->get(route('admin.talent.erasure.show',$request->id))->assertForbidden();
        $this->post(route('admin.talent.erasure.prepare',$request->id))->assertForbidden();
        $this->post(route('admin.talent.erasure.approve',$request->id))->assertForbidden();
        $this->assertSame(0,DB::table('talent_erasure_reviews')->count());
        $this->assertTrue(Candidate::whereKey($candidate->id)->exists());
    }

    public function test_private_document_bytes_change_fingerprint_without_metadata_change(): void
    {
        Storage::fake('local');
        $tenant=Tenant::create(['slug'=>'fingerprint-test','name'=>'Fingerprint Test']);
        $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Synthetic','email'=>'bytes@example.invalid']);
        $key='talent-private/fingerprint-synthetic.txt';
        Storage::disk('local')->put($key,'ORIGINAL-SYNTHETIC');
        DB::table('talent_documents')->insert([
            'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,
            'disk'=>'local','storage_key'=>$key,
            'original_name'=>'synthetic.txt','mime_type'=>'text/plain',
            'size_bytes'=>18,'scan_status'=>'clean',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $fingerprint=app(CandidateErasureFingerprint::class);
        $first=$fingerprint->digest($tenant->id,$candidate->id);
        Storage::disk('local')->put($key,'MODIFIED-SYNTHETIC');
        $second=$fingerprint->digest($tenant->id,$candidate->id);
        $this->assertNotSame($first,$second);
        $this->assertSame(1,DB::table('talent_documents')->count());
    }
}
