@php
    $cvValues = old() ?: ($profile->fields ?? []);
@endphp
@foreach(\App\Services\Talent\CvFieldSchema::GROUPS as $group => $fields)
<fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
    <legend class="px-2 font-semibold">{{ $group }}</legend>
    <div class="grid gap-3 md:grid-cols-2">
    @foreach($fields as $field=>$label)
        <label class="grid gap-1 text-sm">
            <span>{{ $label }}</span>
            @if(in_array($field,['career_objective','expertise','achievement_headline','technical_skills','software_tools','programming','soft_skills','methodologies','industry_certifications','short_courses','volunteering','publications','speaking','awards','interests','additional_information'],true))
            <textarea name="{{ $field }}" rows="3" maxlength="15000" class="rounded border p-2 text-black">{{ $cvValues[$field] ?? '' }}</textarea>
            @else
            <input name="{{ $field }}" type="{{ $field==='email'?'email':'text' }}" maxlength="15000" value="{{ $cvValues[$field] ?? '' }}" class="rounded border p-2 text-black" @required($field==='first_name')>
            @endif
        </label>
    @endforeach
    </div>
</fieldset>
@foreach(\App\Services\Talent\CvFieldSchema::REPEATABLE as $collectionKey=>$definition)
<fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
    <legend class="px-2 font-semibold">{{ $definition['label'] }}</legend>
    <div data-repeatable-container="{{ $collectionKey }}" class="grid gap-4">
      @foreach(($cvValues[$collectionKey] ?? []) as $entryIndex => $entry)
      <div class="rounded border p-3 grid gap-2 md:grid-cols-2" data-cv-entry>
      @foreach($definition['fields'] as $field=>$label)
        <label class="grid gap-1 text-sm"><span>{{ $label }}</span>
        @if(in_array($field,['duties','achievements','projects','tools','progression','coursework','thesis','college','school'],true))
        <textarea class="rounded border p-2 text-black" rows="2" maxlength="15000" name="{{ $collectionKey }}[{{ $entryIndex }}][{{ $field }}]">{{ $entry[$field] ?? '' }}</textarea>
        @elseif(in_array($field,['present','expected'],true))
        <select class="rounded border p-2 text-black" name="{{ $collectionKey }}[{{ $entryIndex }}][{{ $field }}]">
          <option value="no" @selected(($entry[$field] ?? 'no')==='no')>No</option>
          <option value="yes" @selected(($entry[$field] ?? '')==='yes')>Yes</option>
        </select>
        @else
        <input class="rounded border p-2 text-black" maxlength="255" name="{{ $collectionKey }}[{{ $entryIndex }}][{{ $field }}]" value="{{ $entry[$field] ?? '' }}">
        @endif
        </label>
      @endforeach
      <button type="button" onclick="this.closest('[data-cv-entry]').remove()" class="text-sm underline">Remove this entry</button>
      </div>
      @endforeach
    </div>
    <template id="cv-template-{{ $collectionKey }}">
      <div class="rounded border p-3 grid gap-2 md:grid-cols-2" data-cv-entry>
      @foreach($definition['fields'] as $field=>$label)
        <label class="grid gap-1 text-sm"><span>{{ $label }}</span>
        @if(in_array($field,['duties','achievements','projects','tools','progression','coursework','thesis','college','school'],true))
        <textarea data-cv-field="{{ $field }}" class="rounded border p-2 text-black" rows="2" maxlength="15000"></textarea>
        @elseif(in_array($field,['present','expected'],true))
        <select data-cv-field="{{ $field }}" class="rounded border p-2 text-black">
        <option value="no">No</option><option value="yes">Yes</option></select>
        @else
        <input data-cv-field="{{ $field }}" class="rounded border p-2 text-black" maxlength="255">
        @endif
        </label>
      @endforeach
      <button type="button" onclick="this.closest('[data-cv-entry]').remove()" class="text-sm underline">Remove this entry</button>
      </div>
    </template>
    <button type="button" class="mt-3 rounded border px-4 py-2" data-add-cv-entry="{{ $collectionKey }}">+ Add another {{ $collectionKey==='employment_entries'?'job / promotion':'qualification' }}</button>
</fieldset>
@endforeach
<fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
<legend class="px-2 font-semibold">Formatting and housekeeping</legend>
<div class="grid gap-3 md:grid-cols-2">
@foreach(\App\Services\Talent\CvFieldSchema::SETTINGS as $key=>$label)
<label class="grid gap-1 text-sm">{{ $label }}
@if(in_array($key,['reverse_chronological','proofread','consistent_dates','reference_placeholder']))
<select name="{{ $key }}" class="rounded border p-2 text-black"><option value="no" @selected(($cvValues[$key]??'')==='no')>Not confirmed</option><option value="yes" @selected(($cvValues[$key]??'')==='yes')>Confirmed</option></select>
@else
<input name="{{ $key }}" class="rounded border p-2 text-black" maxlength="255" value="{{ $cvValues[$key]??'' }}">
@endif
</label>
@endforeach
</div>
</fieldset>
<script>
if(!window.raeburnCvFieldsBound){
 window.raeburnCvFieldsBound=true;
 document.addEventListener('click',function(e){
  const btn=e.target.closest('[data-add-cv-entry]');
  if(!btn)return;
  const key=btn.getAttribute('data-add-cv-entry');
  const template=document.getElementById('cv-template-'+key);
  const container=btn.closest('fieldset').querySelector('[data-repeatable-container]');
  if(!template||!container||container.querySelectorAll('[data-cv-entry]').length>=100)return;
  const clone=template.content.cloneNode(true);
  const index=Date.now().toString()+Math.floor(Math.random()*10000).toString();
  clone.querySelectorAll('[data-cv-field]').forEach(field=>{
   const name=field.getAttribute('data-cv-field');
   field.name=key+'['+index+']['+name+']';
   field.removeAttribute('data-cv-field');
  });
  container.appendChild(clone);
 });
}
</script>
