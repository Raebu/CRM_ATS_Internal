<x-admin::layouts>
<x-slot:title>Recruitment Analytics</x-slot:title>
<div class="grid gap-4 p-4">
<h1 class="text-2xl font-semibold dark:text-white">Recruitment analytics</h1>
<p class="dark:text-white">Tenant-scoped operational reporting. No candidate details shown.</p>
<div class="grid grid-cols-1 gap-3 md:grid-cols-3">
<section class="rounded bg-white p-4 dark:bg-gray-900 dark:text-white">Total vacancies: {{ number_format($jobs) }}</section>
<section class="rounded bg-white p-4 dark:bg-gray-900 dark:text-white">Published vacancies: {{ number_format($publishedJobs) }}</section>
<section class="rounded bg-white p-4 dark:bg-gray-900 dark:text-white">Applications: {{ number_format($applications) }}</section>
</div>
<section class="rounded bg-white p-4 dark:bg-gray-900 dark:text-white">
<h2 class="font-semibold">Application stages</h2>
<ul>@forelse($stages as $stage=>$count)<li>{{ $stage }}: {{ number_format($count) }}</li>@empty<li>No applications</li>@endforelse</ul>
</section>
<section class="rounded bg-white p-4 dark:bg-gray-900 dark:text-white">
<h2 class="font-semibold">Sources</h2>
<ul>@forelse($sources as $source=>$count)<li>{{ $source ?: 'Unspecified' }}: {{ number_format($count) }}</li>@empty<li>No source records</li>@endforelse</ul>
</section>
</div></x-admin::layouts>
