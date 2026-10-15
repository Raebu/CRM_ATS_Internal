<x-admin::layouts>
<x-slot:title>Candidate erasure review</x-slot:title>
<div class="p-6 grid gap-5 dark:text-white">
<h1 class="text-2xl font-semibold">Candidate erasure review #{{ $privacyRequest->id }}</h1>
<p>This is a decision review only. Approval does not erase records, send emails, or fulfil an erasure request.</p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<a href="{{ route('admin.talent.privacy.index') }}">Back to privacy requests</a>
<p>Privacy status: {{ $privacyRequest->status }}. Review status: {{ $review->status ?? 'Not started' }}.</p>
<section class="rounded border p-4">
<h2 class="font-semibold">Current candidate data inventory</h2>
<ul>@foreach($plan['counts'] as $category=>$count)<li>{{ str_replace('_',' ',$category) }}: {{ $count }}</li>@endforeach</ul>
<h3 class="font-semibold">Blockers</h3>
<ul>@foreach($plan['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul>
</section>
@if(!$review)
<form method="POST" action="{{ route('admin.talent.erasure.prepare',$privacyRequest->id) }}">
@csrf <button type="submit" class="primary-button">Prepare draft review (no deletion)</button>
</form>
@else
@if($review->status === 'draft')
<form class="grid gap-4" method="POST" action="{{ route('admin.talent.erasure.save',$privacyRequest->id) }}">
@csrf @method('PATCH')
<label>Legal holds and retention analysis
<textarea name="legal_hold_notes" required maxlength="10000" rows="3" class="w-full border rounded p-2">{{ old('legal_hold_notes',$review->legal_hold_notes) }}</textarea></label>
<label>Backup handling and restoration suppression
<textarea name="backup_handling_notes" required maxlength="10000" rows="3" class="w-full border rounded p-2">{{ old('backup_handling_notes',$review->backup_handling_notes) }}</textarea></label>
@foreach($categories as $category)
@php($decision=$decisions->get($category))
<div class="border rounded p-3 grid gap-2">
<h3 class="font-semibold">{{ str_replace('_',' ',$category) }}</h3>
<label>Proposed treatment
<select name="decisions[{{ $category }}][action]" class="border rounded p-2">
@foreach(['review_required','erase','retain'] as $action)
<option value="{{ $action }}" @selected(old('decisions.'.$category.'.action',$decision->proposed_action??'review_required') === $action)>{{ str_replace('_',' ',$action) }}</option>
@endforeach
</select></label>
<label>Legal basis (required for retention)
<input name="decisions[{{ $category }}][legal_basis]" maxlength="128" class="w-full border rounded p-2" value="{{ old('decisions.'.$category.'.legal_basis',$decision->legal_basis??'') }}"></label>
<label>Reason and evidence
<textarea name="decisions[{{ $category }}][rationale]" maxlength="10000" class="w-full border rounded p-2">{{ old('decisions.'.$category.'.rationale',$decision->rationale??'') }}</textarea></label>
</div>
@endforeach
<button type="submit" class="primary-button">Save draft decisions</button>
</form>
<form method="POST" action="{{ route('admin.talent.erasure.approve',$privacyRequest->id) }}">
@csrf
<p>Approval requires a different authorised reviewer and a current inventory. It does not execute deletion.</p>
<button type="submit" class="primary-button">Approve review only</button>
</form>
@else
<p>Approved by reviewer ID {{ $review->approved_by }} at {{ $review->approved_at }}. Deletion remains disabled.</p>
@endif
@endif
</div>
</x-admin::layouts>
