<?php

namespace Tests\Feature\Talent;

use App\Http\Controllers\Talent\TalentController;
use App\Models\Talent\{Candidate,Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role,User};

class RecruitmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private function recruiter(array $permissions): User
    {
        $role=Role::create([
            'name'=>'Talent restricted test role',
            'permission_type'=>'custom',
            'permissions'=>$permissions,
        ]);

        return User::create([
            'name'=>'Talent test user',
            'email'=>'talent-user-'.uniqid().'@example.invalid',
            'password'=>bcrypt('not-a-real-password'),
            'role_id'=>$role->id,
            'status'=>1,
        ]);
    }

    public function test_user_without_talent_permission_cannot_see_recruitment_dashboard(): void
    {
        $this->withoutMiddleware(CanInstall::class);
        $this->actingAs($this->recruiter(['dashboard']),'user');
        $response=$this->get(route('admin.talent.index'));
        $this->assertContains($response->status(),[401,403]);
    }

    public function test_recruiter_with_read_access_cannot_create_candidate(): void
    {
        $this->withoutMiddleware(CanInstall::class);
        $this->actingAs($this->recruiter(['talent']),'user');
        $response=$this->post(route('admin.talent.candidates.store'),[
            'name'=>'Must Be Denied','email'=>'denied@example.invalid',
        ]);
        $this->assertContains($response->status(),[401,403]);
        $this->assertDatabaseMissing('talent_candidates',['email'=>'denied@example.invalid']);
    }

    public function test_explicit_view_permission_allows_recruiter_to_access_candidate_data(): void
    {
        $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
        Candidate::create(['tenant_id'=>$tenant->id,'name'=>'Visible Example','email'=>'visible@example.invalid']);
        $this->actingAs($this->recruiter(['talent','talent.candidates.view']),'user');
        $data=(new TalentController)->index()->getData();
        $this->assertCount(1,$data['candidates']);
    }

    public function test_recruiter_cannot_read_candidate_data_without_separate_permission(): void
    {
        $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
        Candidate::create([
            'tenant_id'=>$tenant->id,
            'name'=>'Private Example',
            'email'=>'private@example.invalid',
        ]);
        $this->actingAs($this->recruiter(['talent']),'user');
        $data=(new TalentController)->index()->getData();
        $this->assertCount(0,$data['candidates']);
        $this->assertCount(0,$data['applications']);
    }
}
