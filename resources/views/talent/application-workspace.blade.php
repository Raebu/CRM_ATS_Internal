<x-admin::layouts>
<x-slot:title>Recruitment Application</x-slot:title>
<div class="grid gap-5 p-4 dark:text-white">
<a href="{{ route('admin.talent.index') }}" class="underline">← Back to Raeburn Talent</a>
<h1 class="text-2xl font-bold">{{ $application->candidate->name }} — {{ $application->job->title }}</h1>
<p>Application #{{ $application->id }} · Current stage: <strong>{{ str_replace('_',' ',$application->stage) }}</strong> · Source: {{ $application->source ?? 'Not recorded' }}</p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Candidate</h2>
<p>{{ $application->candidate->email }} · {{ $application->candidate->phone ?? 'No phone' }}</p>
</section>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Application progression</h2>
<form method="POST" action="{{ route('admin.talent.applications.update',$application->id) }}">@csrf @method('PATCH')
<select name="stage" aria-label="New application stage">@foreach(['applied','screening','review','shortlisted','interview','final_interview','offer','hired','rejected','withdrawn','on_hold','talent_pool'] as $stage)<option value="{{ $stage }}" @selected($application->stage===$stage)>{{ ucwords(str_replace('_',' ',$stage)) }}</option>@endforeach</select>
<button class="primary-button">Update stage</button></form>
<h3 class="font-semibold mt-3">Stage history</h3><ol>@forelse($history as $entry)<li>{{ $entry->created_at }} — {{ $entry->from_stage ?? 'New' }} → {{ $entry->to_stage }}</li>@empty<li>No stage changes yet</li>@endforelse</ol></section>
<section class="rounded bg-white p-4 dark:bg-gray-900"><h2 class="text-lg font-semibold">Interviews</h2>
@forelse($interviews as $interview)
<div class="border-b py-3"><strong>{{ $interview->title }}</strong> · {{ $interview->starts_at }} – {{ $interview->ends_at }} · {{ $interview->status }}
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
<select name="status" aria-label="Offer status"><option value="draft">Draft</option><option value="pending_approval">Pending approval</option><option value="approved">Approved</option><option value="withdrawn">Withdrawn</option></select>
<button class="underline">Update offer</button></form></div>
@empty<p>No offers recorded</p>@endforelse
<h3 class="font-semibold mt-3">New offer draft</h3>
<form method="POST" action="{{ route('admin.talent.offers.store',$application->id) }}" class="grid gap-2 max-w-lg">@csrf
<label>Salary <input type="number" name="salary_amount" min="0" step="0.01"></label>
<label>Currency <input name="currency" value="GBP" minlength="3" maxlength="3" required></label>
<textarea name="conditions" maxlength="20000" placeholder="Offer conditions"></textarea>
<button class="primary-button">Create draft offer</button></form></section>
</div></x-admin::layouts>