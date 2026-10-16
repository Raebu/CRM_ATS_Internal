<x-admin::layouts>
<x-slot:title>Recruitment Email Review</x-slot:title>
<div class="grid gap-4 p-4">
<h1>Recruitment email review</h1>
<p>Recruitment emails require human approval. Provider submission is disabled by default and requires a separately assigned send permission and configured verified sender. Provider acceptance does not prove inbox delivery.</p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<table class="w-full"><thead><tr><th>Draft</th><th>Template</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($deliveries as $delivery)
<tr><td>{{ $delivery->id }}</td><td>{{ $delivery->template_key }}</td><td>{{ $delivery->status }}</td>
<td>@if($delivery->status==='draft')
<form method="POST" action="{{ route('admin.talent.email.approve',$delivery->id) }}">@csrf
<span>Recipient derived from the existing application record</span>
<button type="submit">Approve only</button></form>@elseif($delivery->status==='approved' && config('talent.external_email_enabled',false))
<form method="POST" action="{{ route('admin.talent.email.submit',$delivery->id) }}">@csrf<button type="submit">Submit approved email to provider</button></form>
@elseif($delivery->status==='submitted')<span>Provider receipt: {{ $delivery->provider_message_id }}</span>@endif</td></tr>
@empty<tr><td colspan="4">No drafts yet</td></tr>@endforelse
</tbody></table>{{ $deliveries->links() }}
</div></x-admin::layouts>
