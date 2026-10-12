<x-admin::layouts>
    <x-slot:title>Raeburn Talent</x-slot:title>
    <div class="grid gap-5 p-2">
        <h1 class="text-2xl font-bold dark:text-white">Raeburn Talent</h1>
        <p class="text-gray-600 dark:text-gray-300">Native recruitment workspace — vacancies, candidates and applications</p>
        @if(session('success')) <div class="rounded bg-green-100 p-3 text-green-900">{{ session('success') }}</div> @endif
        @if($errors->any()) <div class="rounded bg-red-100 p-3 text-red-900"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <section class="rounded-lg bg-white p-5 dark:bg-gray-900">
                <h2 class="mb-3 text-lg font-semibold dark:text-white">New vacancy</h2>
                <form method="POST" action="{{ route('admin.talent.jobs.store') }}" class="grid gap-3">@csrf
                    <input class="rounded border p-2" name="title" placeholder="Job title" required maxlength="255">
                    <textarea class="rounded border p-2" name="description" placeholder="Job description" required></textarea>
                    <input class="rounded border p-2" name="location" placeholder="Location">
                    <input class="rounded border p-2" name="employment_type" placeholder="Employment type">
                    <button class="primary-button">Create draft</button>
                </form>
            </section>
            <section class="rounded-lg bg-white p-5 dark:bg-gray-900">
                <h2 class="mb-3 text-lg font-semibold dark:text-white">New candidate</h2>
                <form method="POST" action="{{ route('admin.talent.candidates.store') }}" class="grid gap-3">@csrf
                    <input class="rounded border p-2" name="name" placeholder="Full name" required>
                    <input class="rounded border p-2" type="email" name="email" placeholder="Email" required>
                    <input class="rounded border p-2" name="phone" placeholder="Phone">
                    <button class="primary-button">Add candidate</button>
                </form>
            </section>
            <section class="rounded-lg bg-white p-5 dark:bg-gray-900">
                <h2 class="mb-3 text-lg font-semibold dark:text-white">Record application</h2>
                <form method="POST" action="{{ route('admin.talent.applications.store') }}" class="grid gap-3">@csrf
                    <select class="rounded border p-2" name="job_id" required><option value="">Choose vacancy</option>@foreach($jobs as $job)<option value="{{ $job->id }}">{{ $job->title }}</option>@endforeach</select>
                    <select class="rounded border p-2" name="candidate_id" required><option value="">Choose candidate</option>@foreach($candidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }}</option>@endforeach</select>
                    <input class="rounded border p-2" name="source" placeholder="Source (optional)">
                    <button class="primary-button">Record application</button>
                </form>
            </section>
        </div>
        <section class="overflow-x-auto rounded-lg bg-white p-5 dark:bg-gray-900">
            <h2 class="mb-3 text-lg font-semibold dark:text-white">Vacancies ({{ $jobs->count() }})</h2>
            <table class="w-full text-left dark:text-white"><thead><tr><th class="p-2">Vacancy</th><th class="p-2">Location</th><th class="p-2">Status</th></tr></thead><tbody>
            @forelse($jobs as $job)<tr class="border-t"><td class="p-2">{{ $job->title }}</td><td class="p-2">{{ $job->location }}</td><td class="p-2"><form method="POST" action="{{ route('admin.talent.jobs.update',$job->id) }}">@csrf @method('PATCH')
                <select class="rounded border p-2" name="status" aria-label="Vacancy status">@foreach(['draft','ready','published','paused','closed','archived'] as $status)<option value="{{ $status }}" @selected($job->status===$status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="ml-2 underline">Save</button></form></td></tr>
            @empty<tr><td class="p-2" colspan="3">No vacancies yet</td></tr>@endforelse</tbody></table>
        </section>
        <section class="overflow-x-auto rounded-lg bg-white p-5 dark:bg-gray-900">
            <h2 class="mb-3 text-lg font-semibold dark:text-white">Applications ({{ $applications->count() }})</h2>
            <table class="w-full text-left dark:text-white"><thead><tr><th class="p-2">Candidate</th><th class="p-2">Vacancy</th><th class="p-2">Stage</th></tr></thead><tbody>
            @forelse($applications as $application)<tr class="border-t"><td class="p-2">{{ $application->candidate?->name }}</td><td class="p-2">{{ $application->job?->title }}</td><td class="p-2"><form method="POST" action="{{ route('admin.talent.applications.update',$application->id) }}">@csrf @method('PATCH')
                <select class="rounded border p-2" name="stage" aria-label="Application stage">@foreach(['applied','screening','review','shortlisted','interview','final_interview','offer','hired','rejected','withdrawn','on_hold','talent_pool'] as $stage)<option value="{{ $stage }}" @selected($application->stage===$stage)>{{ ucwords(str_replace('_',' ',$stage)) }}</option>@endforeach</select><button class="ml-2 underline">Save</button></form></td></tr>
            @empty<tr><td class="p-2" colspan="3">No applications yet</td></tr>@endforelse</tbody></table>
        </section>
        <section class="rounded-lg bg-white p-5 dark:bg-gray-900"><h2 class="mb-3 text-lg font-semibold dark:text-white">Candidates ({{ $candidates->count() }})</h2>
            <ul class="grid gap-2 dark:text-white">@forelse($candidates as $candidate)<li>{{ $candidate->name }} — {{ $candidate->email }}</li>@empty<li>No candidates yet</li>@endforelse</ul>
        </section>
    </div>
</x-admin::layouts>
