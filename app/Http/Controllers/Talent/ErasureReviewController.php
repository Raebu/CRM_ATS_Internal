<?php
namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{PrivacyRequest,Tenant};
use App\Services\Talent\{CandidatePrivacyInventory,ErasureReviewApproval};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ErasureReviewController extends Controller
{
    private function tenant(): Tenant
    {
        return Tenant::where('slug', 'raeburn-group')->firstOrFail();
    }

    public function show(int $id)
    {
        $tenant = $this->tenant();
        $request = PrivacyRequest::where('tenant_id', $tenant->id)
            ->where('request_type','erasure')->findOrFail($id);
        $review = DB::table('talent_erasure_reviews')->where('tenant_id',$tenant->id)
            ->where('privacy_request_id',$id)->first();
        $decisions = $review ? DB::table('talent_erasure_decisions')
            ->where('tenant_id',$tenant->id)->where('review_id',$review->id)
            ->get()->keyBy('data_category') : collect();
        return view('talent.erasure-review', [
            'privacyRequest'=>$request, 'review'=>$review, 'decisions'=>$decisions,
            'categories'=>ErasureReviewApproval::CATEGORIES,
            'plan'=>app(\App\Services\Talent\CandidateErasurePlan::class)
                ->build($tenant->id,$request->candidate_id),
        ]);
    }

    public function prepare(int $id)
    {
        $tenant=$this->tenant();
        $actor=auth()->guard('user')->id();
        DB::transaction(function () use ($tenant,$id,$actor) {
            $request=PrivacyRequest::where('tenant_id',$tenant->id)
                ->where('request_type','erasure')->lockForUpdate()->findOrFail($id);
            if ($request->status !== 'in_review' || !$request->verified_at
                || !$request->identity_evidence_reviewed_at) {
                throw ValidationException::withMessages(['erasure_review'=>'Identity verification and active review required.']);
            }
            $existing=DB::table('talent_erasure_reviews')
                ->where('tenant_id',$tenant->id)->where('privacy_request_id',$id)->exists();
            if ($existing) {
                throw ValidationException::withMessages(['erasure_review'=>'A review already exists.']);
            }
            $digest=hash('sha256',json_encode(
                app(CandidatePrivacyInventory::class)->inspect($tenant->id,$request->candidate_id),
                JSON_THROW_ON_ERROR));
            $reviewId=DB::table('talent_erasure_reviews')->insertGetId([
                'tenant_id'=>$tenant->id,'privacy_request_id'=>$id,
                'candidate_id'=>$request->candidate_id,'prepared_by'=>$actor,
                'status'=>'draft','inventory_digest'=>$digest,'created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach (ErasureReviewApproval::CATEGORIES as $category) {
                DB::table('talent_erasure_decisions')->insert([
                    'tenant_id'=>$tenant->id,'review_id'=>$reviewId,
                    'data_category'=>$category,'proposed_action'=>'review_required',
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        });
        return redirect()->route('admin.talent.erasure.show',$id)->with('success','Draft erasure review created. Deletion remains disabled.');
    }

    public function save(Request $request,int $id)
    {
        $data=$request->validate([
            'legal_hold_notes'=>['required','string','max:10000'],
            'backup_handling_notes'=>['required','string','max:10000'],
            'decisions'=>['required','array','size:'.count(ErasureReviewApproval::CATEGORIES)],
            'decisions.*.action'=>['required',Rule::in(['review_required','erase','retain'])],
            'decisions.*.rationale'=>['nullable','string','max:10000'],
            'decisions.*.legal_basis'=>['nullable','string','max:128'],
        ]);
        $tenant=$this->tenant();
        $expected=ErasureReviewApproval::CATEGORIES;
        if (array_diff(array_keys($data['decisions']),$expected)
            || array_diff($expected,array_keys($data['decisions']))) {
            throw ValidationException::withMessages(['decisions'=>'Every expected category must be reviewed.']);
        }
        DB::transaction(function () use ($tenant,$id,$data) {
            $review=DB::table('talent_erasure_reviews')->where('tenant_id',$tenant->id)
                ->where('privacy_request_id',$id)->lockForUpdate()->first();
            if (!$review || $review->status!=='draft') abort(409);
            DB::table('talent_erasure_reviews')->where('id',$review->id)->update([
                'legal_hold_notes'=>$data['legal_hold_notes'],
                'backup_handling_notes'=>$data['backup_handling_notes'],'updated_at'=>now(),
            ]);
            foreach (ErasureReviewApproval::CATEGORIES as $category) {
                $decision=$data['decisions'][$category];
                DB::table('talent_erasure_decisions')->where('tenant_id',$tenant->id)
                    ->where('review_id',$review->id)->where('data_category',$category)->update([
                        'proposed_action'=>$decision['action'],
                        'rationale'=>$decision['rationale']??null,
                        'legal_basis'=>$decision['legal_basis']??null,
                        'reviewed_by'=>auth()->guard('user')->id(),
                        'reviewed_at'=>now(),'updated_at'=>now(),
                    ]);
            }
        });
        return back()->with('success','Draft review saved; no records deleted.');
    }

    public function approve(int $id, ErasureReviewApproval $approval)
    {
        $tenant=$this->tenant();
        $review=DB::table('talent_erasure_reviews')->where('tenant_id',$tenant->id)
            ->where('privacy_request_id',$id)->first();
        if (!$review) abort(404);
        $approval->approve($tenant->id,$review->id,auth()->guard('user')->id());
        return back()->with('success','Independent review approved. Erasure execution remains disabled.');
    }
}
