<?php
namespace App\Http\Controllers\Talent;
use App\Http\Controllers\Controller;
use App\Services\Talent\TenantRecords;
use App\Models\Talent\{Candidate,DomainEvent,TalentPool,Tenant};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class TalentPoolController extends Controller {
 private function tenant():Tenant {return Tenant::where('slug','raeburn-group')->firstOrFail();}
 public function store(Request $request) {
  $tenant=$this->tenant();$data=$request->validate([
   'name'=>['required','string','max:255',Rule::unique('talent_pools','name')->where('tenant_id',$tenant->id)],
   'description'=>['nullable','string','max:5000']
  ]);
  DB::transaction(function()use($tenant,$data){
   $pool=TalentPool::create($data+['tenant_id'=>$tenant->id]);
   DomainEvent::create(['tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),
    'event_type'=>'talent_pool.created.v1','event_version'=>1,'aggregate_type'=>'talent_pool',
    'aggregate_id'=>$pool->id,'payload'=>[]]);
  });
  return back()->with('success','Talent pool created.');
 }
 public function addCandidate(Request $request,int $id) {
  $tenant=$this->tenant();$pool=TenantRecords::pool($tenant->id,$id);
  $data=$request->validate(['candidate_id'=>['required','integer',Rule::exists('talent_candidates','id')->where('tenant_id',$tenant->id)]]);
  DB::transaction(function()use($tenant,$pool,$data){
   $candidate=TenantRecords::candidate($tenant->id,$data['candidate_id']);
   $inserted=DB::table('talent_pool_members')->insertOrIgnore([
    'tenant_id'=>$tenant->id,'pool_id'=>$pool->id,'candidate_id'=>$candidate->id,
    'added_at'=>now(),'created_at'=>now(),'updated_at'=>now()
   ]);
   if($inserted)DomainEvent::create(['tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),
    'event_type'=>'talent_pool.member_added.v1','event_version'=>1,'aggregate_type'=>'talent_pool',
    'aggregate_id'=>$pool->id,'payload'=>['candidate_id'=>$candidate->id]]);
  });
  return back()->with('success','Talent pool membership saved.');
 }
}
