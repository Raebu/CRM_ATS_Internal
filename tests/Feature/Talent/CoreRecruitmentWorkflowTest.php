<?php

namespace Tests\Feature\Talent;

use App\Models\Talent\{Application, Candidate, DomainEvent, Job, StageHistory, Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Webkul\Installer\Http\Middleware\CanInstall;
use Webkul\User\Models\{Role, User};

class CoreRecruitmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): Tenant
    {
        $this->withoutMiddleware(CanInstall::class);
        $role = Role::create(['name' => 'Talent E2E test administrator', 'permission_type' => 'all', 'permissions' => []]);
        $user = User::create([
            'name' => 'Synthetic ATS Tester',
            'email' => 'synthetic-ats-' . uniqid() . '@example.invalid',
            'password' => bcrypt('synthetic-test-only'),
            'role_id' => $role->id,
            'status' => 1,
        ]);
        $this->actingAs($user, 'user');

        return Tenant::firstOrCreate(['slug' => 'raeburn-group'], ['name' => 'The Raeburn Group']);
    }

    public function test_creating_job_candidate_and_application_writes_auditable_records(): void
    {
        $tenant = $this->signIn();
        $this->post(route('admin.talent.jobs.store'), [
            'title' => 'Synthetic ATS Vacancy', 'description' => 'Integration testing only',
        ])->assertRedirect();

        $job = Job::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('draft', $job->status);

        $this->post(route('admin.talent.candidates.store'), [
            'name' => 'Synthetic ATS Candidate', 'email' => 'CANDIDATE@example.invalid',
        ])->assertRedirect();

        $candidate = Candidate::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('candidate@example.invalid', $candidate->email);

        $this->post(route('admin.talent.applications.store'), [
            'candidate_id' => $candidate->id, 'job_id' => $job->id,
        ])->assertRedirect();

        $application = Application::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('applied', $application->stage);
        $this->assertDatabaseHas('talent_stage_history', [
            'tenant_id' => $tenant->id, 'application_id' => $application->id,
            'to_stage' => 'applied',
        ]);
        foreach (['job.created.v1', 'candidate.created.v1', 'application.created.v1'] as $event) {
            $this->assertTrue(DomainEvent::where('tenant_id', $tenant->id)->where('event_type', $event)->exists(), $event);
        }
    }

    public function test_duplicate_candidate_and_application_are_rejected(): void
    {
        $tenant = $this->signIn();
        $candidate = Candidate::create(['tenant_id' => $tenant->id, 'name' => 'Existing', 'email' => 'duplicate@example.invalid']);
        $job = Job::create(['tenant_id' => $tenant->id, 'slug' => 'dup-role', 'title' => 'Dup Role', 'description' => 'Test']);
        Application::create(['tenant_id' => $tenant->id, 'candidate_id' => $candidate->id, 'job_id' => $job->id, 'stage' => 'applied']);

        $this->from(route('admin.talent.index'))->post(route('admin.talent.candidates.store'), [
            'name' => 'Duplicate', 'email' => 'DUPLICATE@example.invalid',
        ])->assertSessionHasErrors('email');

        $this->from(route('admin.talent.index'))->post(route('admin.talent.applications.store'), [
            'candidate_id' => $candidate->id, 'job_id' => $job->id,
        ])->assertSessionHasErrors('candidate_id');

        $this->assertSame(1, Candidate::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, Application::where('tenant_id', $tenant->id)->count());
    }

    public function test_allowed_stage_change_writes_history_and_rejected_skip_does_not(): void
    {
        $tenant = $this->signIn();
        $candidate = Candidate::create(['tenant_id' => $tenant->id, 'name' => 'Stage Test', 'email' => 'stage@example.invalid']);
        $job = Job::create(['tenant_id' => $tenant->id, 'slug' => 'stage-role', 'title' => 'Stage Role', 'description' => 'Test']);
        $application = Application::create(['tenant_id' => $tenant->id, 'candidate_id' => $candidate->id, 'job_id' => $job->id, 'stage' => 'applied']);

        $this->patch(route('admin.talent.applications.update', $application->id), ['stage' => 'screening'])->assertRedirect();
        $this->assertSame('screening', $application->fresh()->stage);
        $this->assertDatabaseHas('talent_stage_history', [
            'application_id' => $application->id, 'from_stage' => 'applied', 'to_stage' => 'screening',
        ]);
        $before = StageHistory::where('application_id', $application->id)->count();
        $this->from(route('admin.talent.index'))->patch(route('admin.talent.applications.update', $application->id), [
            'stage' => 'hired',
        ])->assertSessionHasErrors();
        $this->assertSame('screening', $application->fresh()->stage);
        $this->assertSame($before, StageHistory::where('application_id', $application->id)->count());
        $this->assertSame(1, DomainEvent::where('event_type', 'workflow.stage_changed.v1')->where('aggregate_id', $application->id)->count());
    }

    public function test_unapproved_job_publishing_is_blocked(): void
    {
        $tenant = $this->signIn();
        config()->set('talent.job_publishing_enabled', false);
        $job = Job::create(['tenant_id' => $tenant->id, 'slug' => 'safety-role', 'title' => 'Safety Role', 'description' => 'Test', 'status' => 'ready']);

        $this->from(route('admin.talent.index'))->patch(route('admin.talent.jobs.update', $job->id), [
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->assertSame('ready', $job->fresh()->status);
        $this->assertFalse(DomainEvent::where('event_type', 'job.updated.v1')->where('aggregate_id', $job->id)->exists());
    }
}
