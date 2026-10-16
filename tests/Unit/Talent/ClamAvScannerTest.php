<?php

namespace Tests\Unit\Talent;

use App\Services\Talent\ClamAvScanner;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ClamAvScannerTest extends TestCase
{
    public function test_invalid_storage_key_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        app(ClamAvScanner::class)->scan('../outside.pdf');
    }

    public function test_unavailable_scanner_never_reports_clean(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('talent-private/test.pdf', '%PDF-1.4 test');
        config()->set('talent.clamav_host', '127.0.0.1');
        config()->set('talent.clamav_port', 1);
        $this->expectException(RuntimeException::class);
        app(ClamAvScanner::class)->scan('talent-private/test.pdf');
    }
}
