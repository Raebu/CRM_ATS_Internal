<?php

namespace App\Services\Talent;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Fail-closed ClamAV daemon scanner; no file is marked clean on errors. */
class ClamAvScanner
{
    public function scan(string $storageKey): string
    {
        $disk = Storage::disk('local');

        if (!str_starts_with($storageKey, 'talent-private/') || str_contains($storageKey, '..')) {
            throw new RuntimeException('Invalid document storage key.');
        }

        $stream = $disk->readStream($storageKey);
        if (!is_resource($stream)) {
            throw new RuntimeException('Cannot open private document.');
        }

        $host = (string) config('talent.clamav_host', '127.0.0.1');
        $port = (int) config('talent.clamav_port', 3310);
        $maxBytes = 5 * 1024 * 1024;

        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $error, 5);
        if (!is_resource($socket)) {
            fclose($stream);
            throw new RuntimeException('Malware scanner unavailable.');
        }

        stream_set_timeout($socket, 20);
        try {
            $this->writeAll($socket, "zINSTREAM\0");
            $size = 0;
            while (!feof($stream)) {
                $chunk = fread($stream, 65536);
                if ($chunk === false) {
                    throw new RuntimeException('Cannot read document.');
                }
                if ($chunk === '') {
                    break;
                }
                $size += strlen($chunk);
                if ($size > $maxBytes) {
                    throw new RuntimeException('Document exceeds scan limit.');
                }
                $this->writeAll($socket, pack('N', strlen($chunk)) . $chunk);
            }
            $this->writeAll($socket, pack('N', 0));
            $reply = stream_get_line($socket, 4096, "\0");
            if ($reply === false) {
                throw new RuntimeException('Scanner did not respond.');
            }
            if (str_ends_with($reply, ' OK')) {
                return 'clean';
            }
            if (str_contains($reply, ' FOUND')) {
                return 'infected';
            }
            throw new RuntimeException('Scanner returned an error.');
        } finally {
            fclose($stream);
            fclose($socket);
        }
    }

    private function writeAll($stream, string $bytes): void
    {
        while ($bytes !== '') {
            $written = fwrite($stream, $bytes);
            if ($written === false || $written === 0) {
                throw new RuntimeException('Scanner write failed.');
            }
            $bytes = substr($bytes, $written);
        }
    }
}
