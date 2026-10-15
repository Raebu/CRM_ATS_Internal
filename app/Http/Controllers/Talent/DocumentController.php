<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{Application, CvProfile, Document, DomainEvent, Tenant};
use App\Services\Talent\ClamAvScanner;
use App\Services\Talent\PdfSanitizer;
use App\Services\Talent\CvProfileBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function upload(Request $request, int $id, ClamAvScanner $scanner, PdfSanitizer $sanitizer, CvProfileBuilder $builder)
    {
        abort_unless(config('talent.cv_uploads_enabled', false), 503, 'CV uploads are temporarily disabled pending malware-scanning verification.');
        $tenant = Tenant::where('slug', 'raeburn-group')->firstOrFail();
        $application = Application::with(['candidate', 'job'])
            ->where('tenant_id', $tenant->id)->findOrFail($id);
        abort_unless(
            $application->candidate?->tenant_id === $tenant->id
            && $application->job?->tenant_id === $tenant->id,
            404
        );

        // Initially support PDF CVs only. Never rely on client MIME/filename alone.
        $validated = $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf', 'max:4096'],
        ]);
        $file = $validated['cv'];
        $stream = fopen($file->getRealPath(), 'rb');
        $signature = is_resource($stream) ? fread($stream, 5) : false;
        if (is_resource($stream)) {
            fclose($stream);
        }
        if ($signature !== '%PDF-') {
            throw ValidationException::withMessages(['cv' => 'A valid PDF document is required.']);
        }

        $idempotentName = (string) Str::uuid().'.pdf';
        $quarantineKey = 'talent-private/quarantine/'.$tenant->id.'/'.$idempotentName;
        $key = 'talent-private/sanitized/'.$tenant->id.'/'.$idempotentName;
        $disk = Storage::disk('local');
        $uploaded = fopen($file->getRealPath(), 'rb');
        if (!is_resource($uploaded)) {
            throw ValidationException::withMessages(['cv' => 'The document could not be read.']);
        }
        try {
            if (!$disk->put($quarantineKey, $uploaded)) {
                throw new \RuntimeException('Private storage write failed.');
            }
        } finally {
            fclose($uploaded);
        }

        // Fail closed: quarantine, scan original, reconstruct in isolation,
        // scan reconstructed content, then and only then publish metadata.
        $approved = false;
        $extractedText = '';
        try {
            if ($scanner->scan($quarantineKey) !== 'clean') {
                throw new \RuntimeException('Original PDF failed malware scanning.');
            }
            $extractedText = $sanitizer->sanitize($quarantineKey, $key);
            if ($scanner->scan($key) !== 'clean') {
                throw new \RuntimeException('Reconstructed PDF failed malware scanning.');
            }
            $approved = true;
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'cv' => 'The document could not pass security validation and was not retained.',
            ]);
        } finally {
            $disk->delete($quarantineKey);
            if (!$approved) {
                $disk->delete($key);
            }
        }

        // The rasterised intermediate is a processing artifact, not a recruiter CV.
        $disk->delete($key);
        [$fields, $flags] = $builder->extract(
            $extractedText,
            $application->candidate->name,
            $application->candidate->email
        );
        // AI is an optional, separately approved assistive source, never authoritative.
        // Fail safely to manual review if an approved provider is unavailable.
        try {
            $ai = app(\App\Services\Talent\CvAiAssistant::class);
            if ($ai->enabled()) {
                $suggestions = $ai->suggest($extractedText);
                foreach ($suggestions['fields'] as $field=>$value) {
                    if (!is_string($value) || trim($value)==='') continue;
                    if (!isset($fields[$field]) || $fields[$field]==='') {
                        $fields[$field] = $value;
                    } elseif ($fields[$field]!==$value) {
                        $flags[]='AI extraction conflicts with existing '.$field.'; verify against source.';
                    }
                }
                $flags=array_merge($flags,$suggestions['review_flags']);
            }
        } catch (Throwable $e) {
            report($e);
            $flags[]='AI assistance unavailable; review all extracted information manually.';
        }
        $coverage=app(\App\Services\Talent\CvCoverageAuditor::class)->audit($extractedText,[]);
        if ($coverage['review_required']) {
            $flags[]='Unmapped source lines require review before approval.';
            $fields['additional_information']=trim(($fields['additional_information']??'')."\n".implode("\n",$coverage['unmapped_lines']));
        }
        unset($extractedText);

        CvProfile::create([
            'tenant_id' => $tenant->id,
            'candidate_id' => $application->candidate_id,
            'application_id' => $application->id,
            'status' => 'review_required',
            'fields' => $fields,
            'review_flags' => $flags,
        ]);

        return back()->with('success', 'Original destroyed after secure extraction. Review and approve the structured CV before it can be downloaded.');
    }

    public function approve(Request $request, int $id, ClamAvScanner $scanner, \App\Services\Talent\DetailedCvRenderer $renderer)
    {
        abort_unless(config('talent.cv_uploads_enabled', false), 503);
        $tenant = Tenant::where('slug', 'raeburn-group')->firstOrFail();
        $profile = CvProfile::where('tenant_id', $tenant->id)->findOrFail($id);
        abort_unless($profile->status === 'review_required', 409);
        $application = Application::with('candidate')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($profile->application_id);
        abort_unless($application->candidate_id === $profile->candidate_id
            && $application->candidate?->tenant_id === $tenant->id, 404);

        $rules = [];
        foreach (\App\Services\Talent\CvFieldSchema::GROUPS as $group) {
            foreach ($group as $field=>$label) {
                $rules[$field] = ['nullable','string','max:15000'];
            }
        }
        $rules['first_name']=['required','string','max:255'];
        $rules['last_name']=['nullable','string','max:255'];
        $rules['email']=['nullable','email','max:255'];
        foreach (['linkedin_url','portfolio_url'] as $field) {
            $rules[$field]=['nullable','url','max:2048'];
        }
        foreach (\App\Services\Talent\CvFieldSchema::SETTINGS as $field=>$label) {
            $rules[$field]=['nullable','string','max:255'];
        }
        foreach (\App\Services\Talent\CvFieldSchema::REPEATABLE as $collection=>$group) {
            $rules[$collection]=['sometimes','array','max:100'];
            foreach ($group['fields'] as $field=>$label) {
                $rules[$collection.'.*.'.$field]=['nullable','string','max:15000'];
            }
            foreach (['present','expected'] as $boolean) {
                if(isset($group['fields'][$boolean])) $rules[$collection.'.*.'.$boolean]=['nullable','in:yes,no'];
            }
        }
        $fields=array_replace(\App\Services\Talent\CvFieldSchema::defaults(),$request->validate($rules));
        foreach (['employment_entries','education_entries'] as $collection) {
            $fields[$collection]=array_values($fields[$collection]??[]);
        }
        $key = 'talent-private/sanitized/'.$tenant->id.'/'.(string) Str::uuid().'.pdf';
        $disk = Storage::disk('local');
        try {
            $pdf = $renderer->render($fields);
            if (!$disk->put($key, $pdf)) {
                throw new \RuntimeException('Cannot save structured CV.');
            }
            unset($pdf);
            if ($scanner->scan($key) !== 'clean') {
                throw new \RuntimeException('Generated CV did not pass security scan.');
            }
            DB::transaction(function () use ($tenant, $application, $profile, $fields, $disk, $key) {
                // Race-safe status transition: only the first approval publishes.
                $updated = CvProfile::where('id', $profile->id)
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'review_required')
                    ->update([
                        'status' => 'approved',
                        'fields' => json_encode($fields, JSON_THROW_ON_ERROR),
                        'review_flags' => json_encode([], JSON_THROW_ON_ERROR),
                        'approved_by' => auth()->guard('user')->id(),
                        'approved_at' => now(),
                        'updated_at' => now(),
                    ]);
                if ($updated !== 1) {
                    throw new \RuntimeException('CV has already been approved.');
                }
                $document = Document::create([
                    'tenant_id' => $tenant->id,
                    'candidate_id' => $application->candidate_id,
                    'application_id' => $application->id,
                    'disk' => 'local',
                    'storage_key' => $key,
                    'original_name' => 'Raeburn-formatted-CV.pdf',
                    'mime_type' => 'application/pdf',
                    'size_bytes' => $disk->size($key),
                    'scan_status' => 'clean',
                ]);
                CvProfile::where('id', $profile->id)->update(['source_document_id' => $document->id]);
                DomainEvent::create([
                    'tenant_id' => $tenant->id,
                    'event_id' => (string) Str::uuid(),
                    'event_type' => 'cv.approved.v1',
                    'event_version' => 1,
                    'aggregate_type' => 'document',
                    'aggregate_id' => $document->id,
                    'payload' => ['actor_id' => auth()->guard('user')->id(), 'application_id' => $application->id],
                ]);
            });
        } catch (Throwable $e) {
            $disk->delete($key);
            report($e);
            throw ValidationException::withMessages(['cv' => 'CV generation or security approval failed. Please retry.']);
        }
        return redirect()->route('admin.talent.applications.show', $application->id)
            ->with('success', 'Verified structured CV generated, rescanned and saved.');
    }

    public function download(int $id)
    {
        $tenant = Tenant::where('slug', 'raeburn-group')->firstOrFail();
        $document = Document::where('tenant_id', $tenant->id)->findOrFail($id);

        // Never serve pending, failed, or otherwise unverified uploads.
        abort_unless($document->scan_status === 'clean', 404);
        // Legacy clean documents were not reconstructed. Do not serve them.
        abort_unless(str_starts_with($document->storage_key, 'talent-private/sanitized/'), 404);

        // Explicitly pin storage to the private local disk, not an attacker-supplied disk.
        abort_unless($document->disk === 'local'
            && str_starts_with($document->storage_key, 'talent-private/')
            && !str_contains($document->storage_key, '..'), 404);

        abort_unless(Storage::disk('local')->exists($document->storage_key), 404);

        DomainEvent::create([
            'tenant_id' => $tenant->id,
            'event_id' => (string) Str::uuid(),
            'event_type' => 'document.accessed.v1',
            'event_version' => 1,
            'aggregate_type' => 'document',
            'aggregate_id' => $document->id,
            'payload' => ['actor_id' => auth()->guard('user')->id()],
        ]);

        return Storage::disk('local')->download(
            $document->storage_key,
            'candidate-document-'.$document->id,
            ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']
        );
    }
}
