<?php
namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Jobs\Talent\ScanCandidateDocument;
use App\Models\Talent\{Application,Candidate,Document,DomainEvent,Job,Tenant};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicApplicationController extends Controller
{
 public function index() {
  abort_unless(config('talent.public_applications_enabled', false),404);
  $tenant=Tenant::where('slug','raeburn-group')->firstOrFail();
  $jobs=Job::where('tenant_id',$tenant->id)->where('status','published')
   ->where(fn($q)=>$q->whereNull('closes_at')->orWhere('closes_at','>',now()))
   ->orderByDesc('published_at')->paginate(20);
  return view('talent.careers',compact('jobs'));
 }
 public function show(string $slug) {
  abort_unless(config('talent.public_applications_enabled', false),404);
  $tenant=Tenant::where('slug','raeburn-group')->firstOrFail();
  $job=Job::where('tenant_id',$tenant->id)->where('slug',$slug)->where('status','published')
   ->where(fn($q)=>$q->whereNull('closes_at')->orWhere('closes_at','>',now()))->firstOrFail();
  return view('talent.apply',compact('job'));
 }

 public function store(Request $request,string $slug) {
  abort_unless(config('talent.public_applications_enabled', false),404);
  $tenant=Tenant::where('slug','raeburn-group')->firstOrFail();
  $job=Job::where('tenant_id',$tenant->id)->where('slug',$slug)->where('status','published')
   ->where(fn($q)=>$q->whereNull('closes_at')->orWhere('closes_at','>',now()))->firstOrFail();
  $data=$request->validate([
   'name'=>['required','string','max:255'],
   'email'=>['required','email','max:255'],
   'phone'=>['nullable','string','max:60'],
   'privacy_acknowledged'=>['accepted'],
   'cv'=>['nullable','file','mimes:pdf,doc,docx','max:5120'],
  ]);
  $email=Str::lower($data['email']);
  $documentKey=null;
  try {
   DB::transaction(function()use($request,$tenant,$job,$data,$email,&$documentKey){
    $candidate=Candidate::where('tenant_id',$tenant->id)->where('email',$email)->lockForUpdate()->first();
    if(!$candidate) $candidate=Candidate::create(['tenant_id'=>$tenant->id,'name'=>$data['name'],'email'=>$email,'phone'=>$data['phone']??null]);
    if(Application::where('tenant_id',$tenant->id)->where('candidate_id',$candidate->id)->where('job_id',$job->id)->exists()){
     throw ValidationException::withMessages(['email'=>'An application for this vacancy has already been recorded.']);
    }
    $application=Application::create([
     'tenant_id'=>$tenant->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id,
     'stage'=>'applied','source'=>'raeburn_careers','submitted_at'=>now(),
     'privacy_notice_version'=>'2026-10-draft','privacy_acknowledged_at'=>now()
    ]);
    if($request->hasFile('cv')){
     $file=$request->file('cv');
     $documentKey=$file->store('talent-private/'.Str::uuid(),'local');
     if(!$documentKey) throw new \RuntimeException('Private document storage failed.');
     $document = Document::create([
      'tenant_id'=>$tenant->id,'candidate_id'=>$candidate->id,'application_id'=>$application->id,
      'disk'=>'local','storage_key'=>$documentKey,'original_name'=>$file->getClientOriginalName(),
      'mime_type'=>$file->getMimeType()?:'application/octet-stream',
      'size_bytes'=>$file->getSize(),'scan_status'=>'pending'
     ]);
     ScanCandidateDocument::dispatch($document->id)->afterCommit();
    }
    DomainEvent::create([
     'tenant_id'=>$tenant->id,'event_id'=>(string)Str::uuid(),'event_type'=>'application.created.v1',
     'event_version'=>1,'aggregate_type'=>'application','aggregate_id'=>$application->id,
     'payload'=>['application_id'=>$application->id,'job_id'=>$job->id,'candidate_id'=>$candidate->id]
    ]);
   },3);
  }catch(\Throwable $e) {
   if($documentKey) Storage::disk('local')->delete($documentKey);
   throw $e;
  }
  return redirect()->route('talent.public.thank_you');
 }
 public function thankYou() { return view('talent.thank-you'); }
}
