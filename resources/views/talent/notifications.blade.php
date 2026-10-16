<x-admin::layouts>
<x-slot:title>Talent Notifications</x-slot:title>
<div class="grid gap-4 p-4">
<h1 class="text-2xl font-semibold dark:text-white">Talent notifications</h1>
<p class="text-gray-600 dark:text-gray-300">Internal event notifications only. No external messages are sent from this inbox.</p>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<div class="overflow-x-auto rounded bg-white p-4 dark:bg-gray-900">
<table class="w-full text-left dark:text-white">
<thead><tr><th class="p-2">Event</th><th class="p-2">Status</th><th class="p-2">Created</th><th class="p-2">Action</th></tr></thead>
<tbody>
@forelse($notifications as $notification)
<tr class="border-t">
<td class="p-2">{{ str_replace('_',' ',$notification->kind) }}</td>
<td class="p-2">{{ $notification->status }}</td>
<td class="p-2">{{ $notification->created_at }}</td>
<td class="p-2">
@if($notification->status === 'pending')
<form method="POST" action="{{ route('admin.talent.notifications.acknowledge',$notification->id) }}">@csrf @method('PATCH')
<button class="primary-button" type="submit">Acknowledge</button></form>
@else<span>Acknowledged</span>@endif
</td></tr>
@empty<tr><td colspan="4" class="p-2">No notifications yet</td></tr>@endforelse
</tbody></table>
{{ $notifications->links() }}
</div></div></x-admin::layouts>
