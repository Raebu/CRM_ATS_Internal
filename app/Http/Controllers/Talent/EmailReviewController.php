<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{EmailDelivery,RecruitmentNotification,Tenant};
use App\Services\Talent\{EmailDeliveryPlanner,EmailTemplateCatalog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmailReviewController extends Controller
{
 private function tenant():Tenant {return Tenant::where('slug','raeburn-group')->firstOrFail();}

 public function index()
 {
  $tenant=$this->tenant();
  return view('talent.email-review',[
   'deliveries'=>EmailDelivery::where('tenant_id',$tenant->id)->latest()->paginate(25),
   'templates'=>EmailTemplateCatalog::templates(),
  ]);
 }

 public function draft(Request $request,EmailDeliveryPlanner $planner)
 {
  $tenant=$this->tenant();
  $data=$request->validate([
   'notification_id'=>['required','integer',Rule::exists('talent_notifications','id')->where('tenant_id',$tenant->id)],
   'template_key'=>['required',Rule::in(array_keys(EmailTemplateCatalog::templates()))],
  ]);
  $planner->draft($tenant->id,(int)$data['notification_id'],$data['template_key']);
  return back()->with('success','Draft created for review; no email sent.');
 }

 public function approve(Request $request,int $id)
 {
  $tenant=$this->tenant();
  $data=$request->validate(['recipient_email'=>['required','email','max:255']]);
  DB::transaction(function()use($tenant,$id,$data){
   $delivery=EmailDelivery::where('tenant_id',$tenant->id)->lockForUpdate()->findOrFail($id);
   abort_unless($delivery->status==='draft',409);
   $notice=RecruitmentNotification::where('tenant_id',$tenant->id)->findOrFail($delivery->notification_id);
   abort_unless($notice->status==='pending',409);
   $delivery->update([
    'recipient_email'=>$data['recipient_email'],
    'status'=>'approved',
    'approved_by'=>auth()->guard('user')->id(),
    'approved_at'=>now(),
   ]);
  });
  return back()->with('success','Draft approved, but external sending remains disabled.');
 }
}
