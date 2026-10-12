<?php
namespace App\Http\Controllers\Talent;
use App\Http\Controllers\Controller;
use App\Models\Talent\Application;
use App\Models\Talent\Job;
use App\Models\Talent\Tenant;
use App\Models\Talent\Interview;
use App\Models\Talent\Offer;

class TalentAnalyticsController extends Controller
{
 public function index()
 {
  $tenant=Tenant::where('slug','raeburn-group')->firstOrFail();
  $applicationCount=Application::where('tenant_id',$tenant->id)->count();
  $interviewedApplications=Interview::where('tenant_id',$tenant->id)->distinct('application_id')->count('application_id');
  $offeredApplications=Offer::where('tenant_id',$tenant->id)->distinct('application_id')->count('application_id');
  $acceptedApplications=Offer::where('tenant_id',$tenant->id)->where('status','accepted')->distinct('application_id')->count('application_id');
  $timeToAcceptedOfferDays=Offer::where('talent_offers.tenant_id',$tenant->id)->where('talent_offers.status','accepted')
   ->join('talent_applications','talent_applications.id','=','talent_offers.application_id')
   ->whereColumn('talent_applications.tenant_id','talent_offers.tenant_id')
   ->whereNotNull('talent_offers.accepted_at')->whereNotNull('talent_applications.submitted_at')
   ->whereColumn('talent_offers.accepted_at','>=','talent_applications.submitted_at')
   ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, talent_applications.submitted_at, talent_offers.accepted_at)) / 86400 AS days_average')->value('days_average');
  return view('talent.analytics',[
   'jobs'=>Job::where('tenant_id',$tenant->id)->count(),
   'publishedJobs'=>Job::where('tenant_id',$tenant->id)->where('status','published')->count(),
   'applications'=>$applicationCount,
   'interviewConversion'=>$applicationCount>0?round(100*$interviewedApplications/$applicationCount,1):null,
   'offerConversion'=>$applicationCount>0?round(100*$offeredApplications/$applicationCount,1):null,
   'acceptanceConversion'=>$offeredApplications>0?round(100*$acceptedApplications/$offeredApplications,1):null,
   'averageTimeToAcceptedOfferDays'=>$timeToAcceptedOfferDays===null?null:round((float)$timeToAcceptedOfferDays,1),
   'interviews'=>Interview::where('tenant_id',$tenant->id)->count(),
   'offers'=>Offer::where('tenant_id',$tenant->id)->count(),
   'acceptedOffers'=>Offer::where('tenant_id',$tenant->id)->where('status','accepted')->count(),
   'stages'=>Application::where('tenant_id',$tenant->id)
    ->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total','stage'),
   'sources'=>Application::where('tenant_id',$tenant->id)
    ->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total','source'),
  ]);
 }
}
