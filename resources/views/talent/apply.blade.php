<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Apply — {{ $job->title }} — Raeburn Talent</title></head>
<body style="font:16px system-ui;max-width:660px;margin:3rem auto;padding:1rem;line-height:1.6">
<header><strong>Raeburn Talent</strong></header>
<main><h1>{{ $job->title }}</h1><p>{{ $job->location }}</p><div style="white-space:pre-wrap">{{ $job->description }}</div>
<h2>Apply for this vacancy</h2>
@if($errors->any())<div role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ route('talent.public.apply.store',$job->slug) }}">
@csrf
<p><label>Full name <input name="name" value="{{ old('name') }}" required maxlength="255"></label></p>
<p><label>Email <input type="email" name="email" value="{{ old('email') }}" required></label></p>
<p><label>Telephone (optional) <input name="phone" value="{{ old('phone') }}"></label></p>
<p><label>CV (PDF, DOC, DOCX; maximum 5 MB) <input type="file" name="cv" accept=".pdf,.doc,.docx"></label></p>
<p><label><input type="checkbox" name="privacy_acknowledged" value="1" required> I acknowledge the recruitment privacy information supplied with this vacancy.</label></p>
<button type="submit">Submit application</button></form></main></body></html>
