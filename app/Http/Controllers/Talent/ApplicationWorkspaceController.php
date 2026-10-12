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
   'interviews'=>Interview::where('tenant_id',$tenant->id)->where('application_id',$id)->orderBy('starts_at')->get(),
   'offers'=>Offer::where('tenant_id',$tenant->id)->where('application_id',$id)->orderByDesc('version')->get(),
   'history'=>StageHistory::where('tenant_id',$tenant->id)->where('application_id',$id)->orderByDesc('id')->get(),
  ]);
 }
}
