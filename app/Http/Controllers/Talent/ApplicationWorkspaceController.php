<?php
namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{Application,Interview,Offer,StageHistory,Tenant};

final class ApplicationWorkspaceController extends Controller
{
 public function show(int $id)
 {
  $tenant=Tenant::where('slug','raeburn-group')->firstOrFail();
  $application=Application::with(['candidate','job'])
    ->where('tenant_id',$tenant->id)->findOrFail($id);
  abort_unless($application->candidate && $application->candidate->tenant_id===$tenant->id,404);
  abort_unless($application->job && $application->job->tenant_id===$tenant->id,404);
  return view('talent.application-workspace',[
   'application'=>$application,
   'documents'=>\App\Models\Talent\Document::where('tenant_id',$tenant->id)->where('candidate_id',$application->candidate_id)->where('application_id',$application->id)->orderByDesc('id')->get(),
   'interviews'=>Interview::where('tenant_id',$tenant->id)->where('application_id',$id)->orderBy('starts_at')->get(),
   'offers'=>Offer::where('tenant_id',$tenant->id)->where('application_id',$id)->orderByDesc('version')->get(),
   'history'=>StageHistory::where('tenant_id',$tenant->id)->where('application_id',$id)->orderByDesc('id')->get(),
   'placement'=>\Illuminate\Support\Facades\DB::table('talent_placements')->where('tenant_id',$tenant->id)->where('application_id',$id)->first(),
   'placementTasks'=>\Illuminate\Support\Facades\DB::table('talent_placement_tasks')->where('tenant_id',$tenant->id)->whereIn('placement_id',function($query)use($tenant,$id){$query->select('id')->from('talent_placements')->where('tenant_id',$tenant->id)->where('application_id',$id);})->orderBy('id')->get(),
  ]);
 }
}
