<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{Application, Document, DomainEvent, Tenant};
use App\Services\Talent\ClamAvScanner;
use App\Services\Talent\PdfSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function upload(Request $request, int $id, ClamAvScanner $scanner, PdfSanitizer $sanitizer)
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
        try {
            if ($scanner->scan($quarantineKey) !== 'clean') {
                throw new \RuntimeException('Original PDF failed malware scanning.');
            }
            $sanitizer->sanitize($quarantineKey, $key);
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

        try {
            DB::transaction(function () use ($tenant, $application, $file, $key) {
                $document = Document::create([
                    'tenant_id' => $tenant->id,
                    'candidate_id' => $application->candidate_id,
                    'application_id' => $application->id,
                    'disk' => 'local',
                    'storage_key' => $key,
                    'original_name' => basename($file->getClientOriginalName()),
                    'mime_type' => 'application/pdf',
                    'size_bytes' => $disk->size($key),
                    'scan_status' => 'clean',
                ]);
                DomainEvent::create([
                    'tenant_id' => $tenant->id,
                    'event_id' => (string) Str::uuid(),
                    'event_type' => 'document.uploaded.v1',
                    'event_version' => 1,
                    'aggregate_type' => 'document',
                    'aggregate_id' => $document->id,
                    'payload' => [
                        'actor_id' => auth()->guard('user')->id(),
                        'application_id' => $application->id,
                    ],
                ]);
            });
        } catch (Throwable $e) {
            $disk->delete($key);
            throw $e;
        }

        return back()->with('success', 'PDF CV scanned and saved privately.');
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
