<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{DomainEvent,PrivacyRequest,Tenant};
use App\Services\Talent\TenantRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PrivacyRequestController extends Controller
{
    private function tenant(): Tenant
    {
        return Tenant::where('slug', 'raeburn-group')->firstOrFail();
    }

    public function index()
    {
        $tenant = $this->tenant();

        return view('talent.privacy', [
            'requests' => PrivacyRequest::with('candidate')
                ->where('tenant_id', $tenant->id)->latest()->paginate(30),
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $this->tenant();
        $data = $request->validate([
            'candidate_id' => ['required','integer',Rule::exists('talent_candidates','id')->where('tenant_id',$tenant->id)],
            'request_type' => ['required',Rule::in(['access','rectification','erasure','restriction','objection','portability'])],
            'notes' => ['nullable','string','max:10000'],
        ]);

        TenantRecords::candidate($tenant->id, (int) $data['candidate_id']);

        DB::transaction(function () use ($tenant, $data) {
            $entry = PrivacyRequest::create($data + [
                'tenant_id' => $tenant->id,
                'status' => 'pending_verification',
                'created_by' => auth()->guard('user')->id(),
                'due_at' => now()->addMonthNoOverflow(),
            ]);

            DomainEvent::create([
                'tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),
                'event_type'=>'privacy.request_created.v1','event_version'=>1,
                'aggregate_type'=>'privacy_request','aggregate_id'=>$entry->id,
                'payload'=>['request_type'=>$entry->request_type],
            ]);
        });

        return redirect()->route('admin.talent.privacy.index')->with('success','Privacy request recorded for identity verification.');
    }

    public function update(Request $request,int $id)
    {
        $tenant = $this->tenant();
        $data = $request->validate([
            'status'=>['required',Rule::in(['identity_verified','in_review','resolved','declined'])],
            'notes'=>['nullable','string','max:10000'],
            'resolution_code'=>['required_if:status,resolved,declined',Rule::in(['fulfilled','partially_fulfilled','exemption','unable_to_verify','withdrawn_by_requester'])],
            'resolution_notes'=>['required_if:status,resolved,declined','nullable','string','max:10000'],
        ]);

        DB::transaction(function () use ($tenant,$id,$data) {
            $entry = PrivacyRequest::where('tenant_id',$tenant->id)->lockForUpdate()->findOrFail($id);
            // Do not claim erasure was fulfilled before an audited execution workflow exists.
            if ($entry->request_type === 'erasure'
                && $data['status'] === 'resolved'
                && ($data['resolution_code'] ?? null) === 'fulfilled') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'Erasure cannot be marked fulfilled until verified deletion and retention review are implemented.',
                ]);
            }

            $allowed = [
                'pending_verification'=>['identity_verified','declined'],
                'identity_verified'=>['in_review'],
                'in_review'=>['resolved','declined'],
                'resolved'=>[],
                'declined'=>[],
            ];
            if (!in_array($data['status'], $allowed[$entry->status] ?? [], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Privacy workflow transition is not permitted.']);
            }
            $previous = $entry->status;
            $entry->update([
                'status'=>$data['status'],
                'notes'=>$data['notes']??$entry->notes,
                'reviewed_by'=>auth()->guard('user')->id(),
                'verified_at'=>$data['status']==='identity_verified'?now():$entry->verified_at,
                'resolved_at'=>in_array($data['status'],['resolved','declined'],true)?now():null,
                'resolution_code'=>$data['resolution_code']??null,
                'resolution_notes'=>$data['resolution_notes']??null,
                'identity_evidence_reviewed_at'=>$data['status']==='identity_verified'?now():$entry->identity_evidence_reviewed_at,
            ]);
            DB::table('talent_privacy_status_history')->insert([
                'tenant_id'=>$tenant->id,
                'privacy_request_id'=>$entry->id,
                'previous_status'=>$previous,
                'new_status'=>$entry->status,
                'actor_id'=>auth()->guard('user')->id(),
                'resolution_code'=>$data['resolution_code']??null,
                'resolution_notes'=>$data['resolution_notes']??null,
                'changed_at'=>now(),
            ]);
            DomainEvent::create([
                'tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),
                'event_type'=>'privacy.request_status_changed.v1','event_version'=>1,
                'aggregate_type'=>'privacy_request','aggregate_id'=>$entry->id,
                'payload'=>['previous'=>$previous,'status'=>$entry->status],
            ]);
        });

        return back()->with('success','Privacy request status updated.');
    }
}
