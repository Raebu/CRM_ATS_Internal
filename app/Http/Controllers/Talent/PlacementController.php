<?php
namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{Application,Tenant,DomainEvent};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PlacementController extends Controller {
 private function tenant():Tenant {return Tenant::where('slug','raeburn-group')->firstOrFail();}
 public function store(Request $request,int $id) {
  $tenant=$this->tenant();
  $data=$request->validate(['start_date'=>['required','date'],'handover_notes'=>['nullable','string','max:10000']]);
  DB::transaction(function()use($tenant,$id,$data){
   $app=Application::where('tenant_id',$tenant->id)->lockForUpdate()->findOrFail($id);
   if($app->stage!=='hired')throw ValidationException::withMessages(['application'=>'Only hired applicants can be placed.']);
   if(DB::table('talent_placements')->where('tenant_id',$tenant->id)->where('application_id',$id)->exists())
    throw ValidationException::withMessages(['application'=>'Placement already exists.']);
   $placement=DB::table('talent_placements')->insertGetId([
    'tenant_id'=>$tenant->id,'application_id'=>$id,'start_date'=>$data['start_date'],
    'recruiter_id'=>auth()->guard('user')->id(),'handover_notes'=>$data['handover_notes']??null,
    'status'=>'onboarding','created_at'=>now(),'updated_at'=>now(),
   ]);
   foreach(['contract'=>'Contract and terms','right_to_work'=>'Right-to-work check','references'=>'References confirmed','start_details'=>'Start details confirmed','first_day'=>'First-day arrangements'] as $key=>$title) {
    DB::table('talent_placement_tasks')->insert(['tenant_id'=>$tenant->id,'placement_id'=>$placement,'task_key'=>$key,'title'=>$title,'created_at'=>now(),'updated_at'=>now()]);
   }
   DomainEvent::create(['tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),'event_type'=>'placement.created.v1','event_version'=>1,'aggregate_type'=>'placement','aggregate_id'=>$placement,'payload'=>['application_id'=>$id]]);
  });
  return back()->with('success','Placement and onboarding checklist created.');
 }
 public function task(Request $request,int $id,int $taskId) {
  $tenant=$this->tenant();
  $data=$request->validate(['completed'=>['required','boolean']]);
  DB::transaction(function()use($tenant,$id,$taskId,$data){
   $placement=DB::table('talent_placements')->where('tenant_id',$tenant->id)->where('id',$id)->lockForUpdate()->first();
   if(!$placement)abort(404);
   $task=DB::table('talent_placement_tasks')->where('tenant_id',$tenant->id)->where('placement_id',$id)->where('id',$taskId)->first();
   if(!$task)abort(404);
   DB::table('talent_placement_tasks')->where('id',$taskId)->update(['completed_at'=>$data['completed']?now():null,'completed_by'=>$data['completed']?auth()->guard('user')->id():null,'updated_at'=>now()]);
   DomainEvent::create(['tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),'event_type'=>'placement.task_updated.v1','event_version'=>1,'aggregate_type'=>'placement','aggregate_id'=>$id,'payload'=>['task_key'=>$task->task_key,'completed'=>(bool)$data['completed']]]);
  });
  return back()->with('success','Onboarding task updated.');
 }
}
