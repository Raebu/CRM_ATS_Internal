<?php

namespace Tests\Feature\Talent;

use App\Models\Talent\{Application, Candidate, DomainEvent, Job, Tenant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TalentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_and_application_are_separate_and_events_are_persisted(): void
    {
        $tenant = Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
        $job = Job::create([
            'tenant_id'=>$tenant->id, 'title'=>'Engineer',
            'slug'=>'engineer-test','description'=>'Software engineer',
        ]);
        $candidate = Candidate::create([
            'tenant_id'=>$tenant->id, 'name'=>'Example Candidate','email'=>'example@example.invalid',
        ]);
        $application = DB::transaction(function () use ($tenant,$job,$candidate) {
            $application = Application::create([
                'tenant_id'=>$tenant->id,'job_id'=>$job->id,
                'candidate_id'=>$candidate->id,'stage'=>'applied',
            ]);
            DomainEvent::create([
                'tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),
                'event_type'=>'application.created.v1','event_version'=>1,
                'aggregate_type'=>'application','aggregate_id'=>$application->id,
                'payload'=>['job_id'=>$job->id],
            ]);
            return $application;
        });
        $this->assertSame($candidate->id, $application->candidate->id);
        $this->assertSame($job->id, $application->job->id);
        $this->assertDatabaseHas('talent_events', ['aggregate_id'=>$application->id,'event_type'=>'application.created.v1']);
    }
}
