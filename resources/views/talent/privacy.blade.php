<x-admin::layouts>
<x-slot:title>Recruitment Privacy Requests</x-slot:title>
<div class="grid gap-4 p-4">
<h1 class="text-2xl font-semibold dark:text-white">Recruitment privacy requests</h1>
<p class="dark:text-white">Tracking only: status updates do not export or erase candidate data. Identity verification and legal review are required.</p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<div class="overflow-x-auto rounded bg-white p-4 dark:bg-gray-900">
<table class="w-full text-left dark:text-white"><thead><tr><th>Candidate</th><th>Type</th><th>Status</th><th>Recorded</th><th>Review deadline</th><th>Action</th></tr></thead><tbody>
@forelse($requests as $entry)
<tr class="border-t"><td class="p-2">{{ $entry->candidate?->name }}</td><td class="p-2">{{ $entry->request_type }}</td><td class="p-2">{{ $entry->status }}</td><td class="p-2">{{ $entry->created_at }}</td><td class="p-2">{{ $entry->due_at?->format('Y-m-d') ?? 'Review required' }}</td><td class="p-2">
@if($entry->request_type === 'erasure')<a class="underline" href="{{ route('admin.talent.erasure.show',$entry->id) }}">Erasure review</a>@endif
<form method="POST" action="{{ route('admin.talent.privacy.update',$entry->id) }}">@csrf @method('PATCH')
<select name="status" aria-label="Change privacy request status">
@foreach(['identity_verified','in_review','resolved','declined'] as $status)<option value="{{ $status }}">{{ str_replace('_',' ',$status) }}</option>@endforeach
</select>
<select name="resolution_code" aria-label="Resolution decision"><option value="">Choose resolution where required</option>@foreach(['fulfilled','partially_fulfilled','exemption','unable_to_verify','withdrawn_by_requester'] as $resolution)<option value="{{ $resolution }}">{{ str_replace('_',' ',$resolution) }}</option>@endforeach</select>
<input name="resolution_notes" aria-label="Resolution justification" placeholder="Resolution evidence (required when closed)" maxlength="10000">
<button type="submit" class="primary-button">Update</button></form></td></tr>
@empty<tr><td colspan="6" class="p-2">No privacy requests recorded</td></tr>@endforelse
</tbody></table>{{ $requests->links() }}</div></div></x-admin::layouts>
