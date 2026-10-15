<x-admin::layouts>
<x-slot:title>Recruitment Application</x-slot:title>
<div class="grid gap-5 p-4 dark:text-white">
<a href="{{ route('admin.talent.index') }}" class="underline">← Back to Raeburn Talent</a>
<h1 class="text-2xl font-bold">{{ $application->candidate->name }} — {{ $application->job->title }}</h1>
<p>Application #{{ $application->id }} · Current stage: <strong>{{ str_replace('_',' ',$application->stage) }}</strong> · Source: {{ $application->source ?? 'Not recorded' }}</p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Candidate</h2>
<p>{{ $application->candidate->email }} · {{ $application->candidate->phone ?? 'No phone' }}</p>
</section>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Application progression</h2>
<form method="POST" action="{{ route('admin.talent.applications.update',$application->id) }}">@csrf @method('PATCH')
<select name="stage" aria-label="New application stage">@foreach(['applied','screening','review','shortlisted','interview','final_interview','offer','hired','rejected','withdrawn','on_hold','talent_pool'] as $stage)<option value="{{ $stage }}" @selected($application->stage===$stage)>{{ ucwords(str_replace('_',' ',$stage)) }}</option>@endforeach</select>
<button class="primary-button">Update stage</button></form>
<h3 class="font-semibold mt-3">Stage history</h3><ol>@forelse($history as $entry)<li>{{ $entry->created_at }} — {{ $entry->from_stage ?? 'New' }} → {{ $entry->to_stage }}</li>@empty<li>No stage changes yet</li>@endforelse</ol></section>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">CV documents</h2>
<p class="text-sm">Private PDF documents only, maximum 4 MB. Uploads are quarantined, scanned, isolated, extracted into a form and destroyed. Only approved formatted CVs are downloadable. External candidate uploads remain disabled.</p>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.talent.documents.upload',$application->id) }}" class="grid gap-2 max-w-lg">@csrf
<label>PDF CV <input type="file" name="cv" accept="application/pdf,.pdf" required></label>
<button class="primary-button" @disabled(!config('talent.cv_uploads_enabled', false))>Scan, extract and create review draft</button></form>
@unless(config('talent.cv_uploads_enabled', false))<p class="text-sm" role="status">CV processing is temporarily disabled pending security acceptance tests.</p>@endunless
<h3 class="font-semibold mt-4">Structured CV review</h3>
@forelse($cvProfiles as $profile)
<div class="rounded border border-gray-200 p-4 my-3 dark:border-gray-600">
<p>CV #{{ $profile->id }} — <strong>{{ str_replace('_', ' ', $profile->status) }}</strong></p>
@if($profile->status === 'review_required')
<p class="text-sm">Verify every field against candidate-provided evidence. Extraction is only a draft; it may omit dates or misread columns. Blank fields are permitted except the candidate name.</p>
@foreach($profile->review_flags ?? [] as $flag)<p class="text-sm">{{ $flag }}</p>@endforeach
<form method="POST" action="{{ route('admin.talent.cv-profiles.approve',$profile->id) }}" class="grid gap-3 mt-3">
@csrf
@include('talent.partials.cv-review-fields')
<button class="primary-button" @disabled(!config('talent.cv_uploads_enabled', false))>Confirm verified fields and generate formatted CV</button>
</form>
@else
<p class="text-sm">Reviewed and approved {{ $profile->approved_at }}</p>
@endif
</div>
@empty<p>No extracted CV profiles yet.</p>@endforelse
<h3 class="font-semibold mt-4">Approved recruiter-facing CVs</h3>
<ul class="mt-3">@forelse($documents as $document)<li class="py-1">{{ $document->original_name }} — {{ $document->scan_status }}
@if($document->scan_status==='clean' && str_starts_with($document->storage_key,'talent-private/sanitized/'))<a class="underline" href="{{ route('admin.talent.documents.download',$document->id) }}">Download securely</a>@endif
</li>@empty<li>No CV documents uploaded</li>@endforelse</ul>
</section>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Interviews</h2>
@forelse($interviews as $interview)
<div class="border-b py-3"><strong>{{ $interview->title }}</strong> · {{ $interview->starts_at }} – {{ $interview->ends_at }} · {{ $interview->status }} <a class="underline" href="{{ route('admin.talent.interviews.calendar',$interview->id) }}">Download calendar invite</a>
<form method="POST" action="{{ route('admin.talent.interviews.update',$interview->id) }}">@csrf @method('PATCH')
<select name="status" aria-label="Interview status"><option value="scheduled" @selected($interview->status==='scheduled')>Scheduled</option><option value="completed" @selected($interview->status==='completed')>Completed</option><option value="cancelled" @selected($interview->status==='cancelled')>Cancelled</option></select>
<button class="underline">Update interview</button></form></div>
@empty<p>No interviews scheduled</p>@endforelse
<h3 class="font-semibold mt-3">Schedule interview</h3>
<form method="POST" action="{{ route('admin.talent.interviews.store',$application->id) }}" class="grid gap-2 max-w-lg">@csrf
<input name="title" maxlength="255" placeholder="Interview title" required>
<label>Start <input type="datetime-local" name="starts_at" required></label>
<label>End <input type="datetime-local" name="ends_at" required></label>
<input name="location" maxlength="255" placeholder="Location or meeting link">
<button class="primary-button">Schedule internally</button></form></section>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Offers</h2>
<p class="text-sm">Draft and internal approval only. Delivery and candidate acceptance are disabled until identity and provider verification are complete.</p>
@forelse($offers as $offer)<div class="border-b py-3">Version {{ $offer->version }} · {{ $offer->status }} · {{ $offer->currency }} {{ $offer->salary_amount }}
<form method="POST" action="{{ route('admin.talent.offers.update',$offer->id) }}">@csrf @method('PATCH')
<select name="status" aria-label="Offer status">@foreach(['draft','pending_approval','approved','withdrawn'] as $status)<option value="{{ $status }}" @selected($offer->status===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select>
<button class="underline">Update offer</button></form></div>
@empty<p>No offers recorded</p>@endforelse
<h3 class="font-semibold mt-3">New offer draft</h3>
<form method="POST" action="{{ route('admin.talent.offers.store',$application->id) }}" class="grid gap-2 max-w-lg">@csrf
<label>Salary <input type="number" name="salary_amount" min="0" step="0.01"></label>
<label>Currency <input name="currency" value="GBP" minlength="3" maxlength="3" required></label>
<textarea name="conditions" maxlength="20000" placeholder="Offer conditions"></textarea>
<button class="primary-button">Create draft offer</button></form></section>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Placement and onboarding</h2>
@if($application->stage==='hired')
@if(!$placement)
<form method="POST" action="{{ route('admin.talent.placements.store',$application->id) }}" class="grid gap-2 max-w-lg">@csrf
<label>Start date <input type="date" name="start_date" required></label>
<label>Handover notes <textarea name="handover_notes" maxlength="10000"></textarea></label>
<button class="primary-button">Create placement and onboarding checklist</button></form>
@else
<p>Start: {{ $placement->start_date }} · Assigned recruiter ID: {{ $placement->recruiter_id }} · Status: {{ $placement->status }}</p>
@if($placement->handover_notes)<p class="whitespace-pre-wrap">{{ $placement->handover_notes }}</p>@endif
<h3 class="font-semibold mt-3">Onboarding checklist</h3>
@foreach($placementTasks as $task)<form method="POST" action="{{ route('admin.talent.placements.tasks.update',[$placement->id,$task->id]) }}" class="flex items-center gap-3 py-2">@csrf @method('PATCH')
<span>{{ $task->title }} — {{ $task->completed_at ? 'Complete' : 'Pending' }}</span>
<input type="hidden" name="completed" value="{{ $task->completed_at ? '0' : '1' }}">
<button class="underline">{{ $task->completed_at ? 'Reopen' : 'Mark complete' }}</button></form>@endforeach
@endif
@else<p>Placement becomes available after hiring following a verified accepted offer.</p>@endif
</section>
</div></x-admin::layouts>