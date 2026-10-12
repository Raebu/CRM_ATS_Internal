<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Services\Talent\RecruitmentTransitions;
use App\Models\Talent\{Application, Candidate, DomainEvent, Job, StageHistory, TalentPool, Tenant};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TalentController extends Controller
{
    private function tenant(): Tenant
    {
        // Phase-one single-tenant instance. Never accept a tenant ID from the request.
        return Tenant::where('slug', 'raeburn-group')->firstOrFail();
    }

    public function index()
    {
        $tenant = $this->tenant();
        return view('talent.index', [
            'jobs' => Job::where('tenant_id', $tenant->id)->latest()->get(),
            'candidates' => Candidate::where('tenant_id', $tenant->id)->when(request('candidate_search'), function ($q, $search) { $q->where(function ($inner) use ($search) { $inner->where('name','like','%'.addcslashes($search,'%_\\').'%')->orWhere('email','like','%'.addcslashes($search,'%_\\').'%'); }); })->latest()->limit(50)->get(),
            'talentPools' => TalentPool::withCount('candidates')->where('tenant_id',$tenant->id)->orderBy('name')->get(),
            'applications' => Application::with(['job','candidate'])->where('tenant_id', $tenant->id)->latest()->limit(100)->get(),
        ]);
    }

    public function createJob(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['required','string','max:50000'],
            'location' => ['nullable','string','max:255'],
            'employment_type' => ['nullable','string','max:100'],
        ]);
        $tenant = $this->tenant();
        DB::transaction(function () use ($tenant, $data) {
            $job = Job::create($data + ['tenant_id' => $tenant->id, 'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6))]);
            $this->event($tenant->id, 'job.created.v1', 'job', $job->id, ['status' => 'draft']);
        });
        return back()->with('success','Draft vacancy created.');
    }

    public function updateJob(Request $request, int $id)
    {
        $tenant = $this->tenant();
        $job = Job::where('tenant_id', $tenant->id)->findOrFail($id);
        $data = $request->validate(['status'=>['required',Rule::in(['draft','ready','published','paused','closed','archived'])]]);
        DB::transaction(function () use ($tenant,$job,$data) {
            $previous = $job->status;
            $job->update(['status'=>$data['status'], 'published_at'=>$data['status']==='published' ? ($job->published_at ?? now()) : $job->published_at]);
            $this->event($tenant->id,'job.updated.v1','job',$job->id,['previous'=>$previous,'status'=>$job->status]);
        });
        return back()->with('success','Vacancy status updated.');
    }

    public function createCandidate(Request $request)
    {
        $data = $request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','max:255'],
            'phone'=>['nullable','string','max:60'],
        ]);
        $tenant = $this->tenant();
        $data['email'] = Str::lower($data['email']);
        validator($data, ['email' => [Rule::unique('talent_candidates','email')->where('tenant_id',$tenant->id)]])->validate();
        DB::transaction(function () use ($tenant,$data) {
            $candidate = Candidate::create($data + ['tenant_id'=>$tenant->id]);
            $this->event($tenant->id,'candidate.created.v1','candidate',$candidate->id,[]);
        });
        return back()->with('success','Candidate created.');
    }

    public function createApplication(Request $request)
    {
        $tenant = $this->tenant();
        $data = $request->validate([
            'job_id'=>['required','integer',Rule::exists('talent_jobs','id')->where('tenant_id',$tenant->id)],
            'candidate_id'=>['required','integer',Rule::exists('talent_candidates','id')->where('tenant_id',$tenant->id)],
            'source'=>['nullable','string','max:255'],
        ]);
        $request->validate(['candidate_id'=>Rule::unique('talent_applications','candidate_id')->where('tenant_id',$tenant->id)->where('job_id',$data['job_id'])]);
        DB::transaction(function () use ($tenant,$data) {
            $application = Application::create($data + ['tenant_id'=>$tenant->id,'stage'=>'applied','submitted_at'=>now()]);
            StageHistory::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,'from_stage'=>null,'to_stage'=>'applied','actor_id'=>auth()->guard('user')->id()]);
            $this->event($tenant->id,'application.created.v1','application',$application->id,['job_id'=>$application->job_id,'candidate_id'=>$application->candidate_id]);
        });
        return back()->with('success','Application recorded.');
    }

    public function moveApplication(Request $request, int $id)
    {
        $tenant = $this->tenant();
        $application = Application::where('tenant_id',$tenant->id)->findOrFail($id);
        $data = $request->validate(['stage'=>['required',Rule::in(['applied','screening','review','shortlisted','interview','final_interview','offer','hired','rejected','withdrawn','on_hold','talent_pool'])]]);
        DB::transaction(function () use ($tenant,$application,$data) {
            $locked = Application::where('tenant_id',$tenant->id)->lockForUpdate()->findOrFail($application->id);
            $old = $locked->stage;
            RecruitmentTransitions::application($old,$data['stage']);
            $locked->update($data);
            StageHistory::create(['tenant_id'=>$tenant->id,'application_id'=>$application->id,
                'from_stage'=>$old,'to_stage'=>$locked->stage,
                'actor_id'=>auth()->guard('user')->id()]);
            $this->event($tenant->id,'workflow.stage_changed.v1','application',$application->id,['previous'=>$old,'stage'=>$locked->stage]);
        });
        return back()->with('success','Application stage updated.');
    }

    private function event(int $tenantId,string $eventType,string $type,int $id,array $payload): void
    {
        DomainEvent::create([
            'tenant_id'=>$tenantId,'event_id'=>(string)Str::uuid(),
            'event_type'=>$eventType,'event_version'=>1,
            'aggregate_type'=>$type,'aggregate_id'=>$id,'payload'=>$payload,
        ]);
    }
}
