<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\{Document, DomainEvent, Tenant};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function download(int $id)
    {
        $tenant = Tenant::where('slug', 'raeburn-group')->firstOrFail();
        $document = Document::where('tenant_id', $tenant->id)->findOrFail($id);

        // Never serve pending, failed, or otherwise unverified uploads.
        abort_unless($document->scan_status === 'clean', 404);

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
