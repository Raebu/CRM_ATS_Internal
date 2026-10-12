<?php
namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Services\Talent\RecruitmentTransitions;
use App\Models\Talent\{Application,DomainEvent,Interview,Offer,Tenant};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RecruitmentOperationsController extends Controller
{
 private function tenant():Tenant { return Tenant::where('slug','raeburn-group')->firstOrFail(); }
 private function application(int $id, int $tenantId):Application {
  return Application::where('tenant_id',$tenantId)->findOrFail($id);
 }
 private function record(int $tenantId,string $type,string $aggregate,int $id,array $payload):void {
  DomainEvent::create(['tenant_id'=>$tenantId,'event_id'=>(string)Str::uuid(),
   'event_type'=>$type,'event_version'=>1,'aggregate_type'=>$aggregate,
   'aggregate_id'=>$id,'payload'=>$payload]);
 }
 public function storeInterview(Request $request,int $id) {
  $tenant=$this->tenant(); $application=$this->application($id,$tenant->id);
  $data=$request->validate([
   'title'=>['required','string','max:255'],
   'starts_at'=>['required','date'],
   'ends_at'=>['required','date','after:starts_at'],
   'location'=>['nullable','string','max:255'],
  ]);
  DB::transaction(function()use($tenant,$application,$data){
   $interview=Interview::create($data+['tenant_id'=>$tenant->id,'application_id'=>$application->id]);
   $this->record($tenant->id,'interview.scheduled.v1','interview',$interview->id,['application_id'=>$application->id]);
  });
  return back()->with('success','Interview scheduled.');
 }
 public function updateInterview(Request $request,int $id) {
  $tenant=$this->tenant(); $interview=Interview::where('tenant_id',$tenant->id)->findOrFail($id);
  $data=$request->validate(['status'=>['required',Rule::in(['scheduled','completed','cancelled'])]]);
  DB::transaction(function()use($tenant,$interview,$data){
   $interview->update($data);
   $this->record($tenant->id,'interview.status_changed.v1','interview',$interview->id,['status'=>$interview->status]);
  });
  return back()->with('success','Interview updated.');
 }
 public function storeOffer(Request $request,int $id) {
  $tenant=$this->tenant(); $application=$this->application($id,$tenant->id);
  $data=$request->validate([
   'salary_amount'=>['nullable','numeric','min:0','max:9999999999'],
   'currency'=>['required','string','size:3'],
   'conditions'=>['nullable','string','max:20000']
  ]);
  DB::transaction(function()use($tenant,$application,$data){
   Application::where('tenant_id',$tenant->id)->lockForUpdate()->findOrFail($application->id);
   $version=(int)Offer::where('tenant_id',$tenant->id)->where('application_id',$application->id)->max('version')+1;
   $offer=Offer::create($data+['tenant_id'=>$tenant->id,'application_id'=>$application->id,'version'=>$version]);
   $this->record($tenant->id,'offer.created.v1','offer',$offer->id,['application_id'=>$application->id,'version'=>$version]);
  });
  return back()->with('success','Draft offer created.');
 }
 public function updateOffer(Request $request,int $id) {
  $tenant=$this->tenant(); $offer=Offer::where('tenant_id',$tenant->id)->findOrFail($id);
  $data=$request->validate(['status'=>['required',Rule::in(['draft','pending_approval','approved','sent','accepted','declined','withdrawn'])]]);
  DB::transaction(function()use($tenant,$offer,$data){
   $locked=Offer::where('tenant_id',$tenant->id)->lockForUpdate()->findOrFail($offer->id);
   $previous=$locked->status;
   RecruitmentTransitions::offer($previous,$data['status']);
   $locked->update(['status'=>$data['status'],
    'accepted_at'=>$data['status']==='accepted' ? now() : $locked->accepted_at]);
   $this->record($tenant->id,'offer.status_changed.v1','offer',$offer->id,['previous'=>$previous,'status'=>$locked->status]);
  });
  return back()->with('success','Offer updated.');
 }
}
