<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Careers — The Raeburn Group</title>
<style>body{font:1rem/1.6 system-ui,sans-serif;max-width:850px;margin:0 auto;padding:2rem 1rem;color:#182230}a{color:#154b96}a:focus-visible{outline:3px solid #f4a340}article{padding:1.25rem 0;border-bottom:1px solid #bbb}small{color:#536077}h1,h2{line-height:1.25}</style></head>
<body><main><h1>Career opportunities</h1><p>Explore our current vacancies. Your application is processed according to our recruitment privacy notice.</p>
@if($jobs->isEmpty())<p>No vacancies are currently open for applications.</p>@endif
@foreach($jobs as $job)<article><h2><a href="{{ route('talent.public.apply.show',$job->slug) }}">{{ $job->title }}</a></h2>
<p>{{ $job->location ?: 'Location to be confirmed' }} · {{ $job->employment_type ?: 'Employment type to be confirmed' }}</p>
@if($job->closes_at)<small>Closes {{ $job->closes_at->format('j F Y') }}</small>@endif
</article>@endforeach
{{ $jobs->links() }}</main></body></html>