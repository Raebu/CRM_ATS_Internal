<?php
namespace App\Http\Controllers\Talent;
use App\Http\Controllers\Controller;
use App\Models\Talent\Application;
use App\Models\Talent\Job;
use App\Models\Talent\Tenant;

class TalentAnalyticsController extends Controller
{
 public function index()
 {
  $tenant=Tenant::where('slug','raeburn-group')->firstOrFail();
  return view('talent.analytics',[
   'jobs'=>Job::where('tenant_id',$tenant->id)->count(),
   'publishedJobs'=>Job::where('tenant_id',$tenant->id)->where('status','published')->count(),
   'applications'=>Application::where('tenant_id',$tenant->id)->count(),
   'stages'=>Application::where('tenant_id',$tenant->id)
    ->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total','stage'),
   'sources'=>Application::where('tenant_id',$tenant->id)
    ->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total','source'),
  ]);
 }
}
