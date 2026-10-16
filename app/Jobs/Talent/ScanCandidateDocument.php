<?php

namespace App\Jobs\Talent;

use App\Models\Talent\Document;
use App\Services\Talent\ClamAvScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ScanCandidateDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public int $documentId)
    {
        $this->onQueue('talent-scans');
    }

    public function handle(ClamAvScanner $scanner): void
    {
        $document = Document::findOrFail($this->documentId);
        if ($document->scan_status !== 'pending') {
            return;
        }
        // Do not permit arbitrary storage disks to be scanned.
        if ($document->disk !== 'local') {
            $document->update(['scan_status' => 'scan_failed']);
            return;
        }
        $document->update(['scan_status' => $scanner->scan($document->storage_key)]);
    }

    public function failed(?Throwable $exception): void
    {
        Document::whereKey($this->documentId)
            ->where('scan_status', 'pending')
            ->update(['scan_status' => 'scan_failed']);
    }
}
