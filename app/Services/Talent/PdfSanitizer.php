<?php

namespace App\Services\Talent;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Rasterise untrusted PDFs in a network-isolated bubblewrap container, then
 * construct a fresh PDF from page images. Never expose the original upload.
 *
 * Requires: bubblewrap, poppler-utils, qpdf, python3-img2pdf.
 * These dependencies are deliberately required; there is no insecure fallback.
 */
class PdfSanitizer
{
    public function sanitize(string $sourceKey, string $outputKey): string
    {
        $disk = Storage::disk('local');
        foreach ([$sourceKey, $outputKey] as $key) {
            if (!str_starts_with($key, 'talent-private/') || str_contains($key, '..')) {
                throw new RuntimeException('Unsafe document key.');
            }
        }
        if ($sourceKey === $outputKey) {
            throw new RuntimeException('Source and output must differ.');
        }
        foreach (['/usr/bin/bwrap', '/usr/bin/pdfinfo', '/usr/bin/pdftoppm', '/usr/bin/qpdf', '/usr/bin/python3', '/usr/bin/tesseract'] as $binary) {
            if (!is_executable($binary)) {
                throw new RuntimeException('PDF isolation dependencies unavailable.');
            }
        }

        $workspace = sys_get_temp_dir().'/talent-pdf-'.bin2hex(random_bytes(16));
        if (!mkdir($workspace, 0700)) {
            throw new RuntimeException('Unable to create private PDF workspace.');
        }

        try {
            $input = $disk->readStream($sourceKey);
            if (!is_resource($input)) {
                throw new RuntimeException('Cannot read PDF quarantine item.');
            }
            try {
                $target = fopen($workspace.'/input.pdf', 'xb');
                if (!is_resource($target)) {
                    throw new RuntimeException('Cannot stage PDF.');
                }
                try {
                    $size = 0;
                    while (!feof($input)) {
                        $chunk = fread($input, 65536);
                        if ($chunk === false) {
                            throw new RuntimeException('PDF read error.');
                        }
                        $size += strlen($chunk);
                        if ($size > 4 * 1024 * 1024) {
                            throw new RuntimeException('PDF exceeds maximum size.');
                        }
                        if ($chunk !== '' && fwrite($target, $chunk) !== strlen($chunk)) {
                            throw new RuntimeException('PDF staging error.');
                        }
                    }
                } finally {
                    fclose($target);
                }
            } finally {
                fclose($input);
            }

            // A single sandbox command holds both parser and renderer. No host
            // network, no writable filesystem beyond the private workspace.
            $script = <<<'SH'
set -eu
qpdf --check /work/input.pdf >/dev/null 2>&1
# Reject PDFs with active actions or embedded attachments even before CDR.
# qpdf JSON serialises PDF names as /Name strings.
qpdf --json /work/input.pdf > /work/structure.json 2>/dev/null
if grep -Eq '"/(JavaScript|JS|EmbeddedFiles|Launch|RichMedia|OpenAction|AA|XFA|AcroForm)"' /work/structure.json; then
    exit 35
fi
qpdf --is-encrypted /work/input.pdf >/dev/null 2>&1 && exit 31
pdfinfo /work/input.pdf > /work/info.txt
# Text extraction happens only inside the same isolated process, never on the host.
pdftotext -layout -enc UTF-8 /work/input.pdf /work/extracted.txt >/dev/null 2>&1
pages=$(sed -n 's/^Pages:[[:space:]]*//p' /work/info.txt | head -n1)
case "$pages" in ''|*[!0-9]*) exit 32;; esac
[ "$pages" -ge 1 ] && [ "$pages" -le 20 ] || exit 33
# 110 DPI bounded rasterisation strips scripts, links, attachments and actions.
pdftoppm -r 110 -f 1 -l "$pages" -png /work/input.pdf /work/page >/dev/null 2>&1
set -- /work/page-*.png
[ -f "$1" ] || exit 34
# OCR only if the document has little machine-readable text. Keep isolated.
if [ "$(wc -c < /work/extracted.txt)" -lt 80 ]; then
  : > /work/ocr.txt
  for page in "$@"; do
    tesseract "$page" stdout 2>/dev/null >> /work/ocr.txt || exit 36
    printf '\n' >> /work/ocr.txt
  done
  mv /work/ocr.txt /work/extracted.txt
fi
python3 -m img2pdf "$@" -o /work/rebuilt.pdf
qpdf --check /work/rebuilt.pdf >/dev/null 2>&1
SH;
            $process = new Process([
                '/usr/bin/bwrap', '--die-with-parent', '--new-session', '--unshare-net',
                '--ro-bind', '/usr', '/usr', '--ro-bind', '/lib', '/lib',
                '--ro-bind-try', '/lib64', '/lib64', '--ro-bind-try', '/bin', '/bin',
                '--dev', '/dev', '--proc', '/proc',
                '--tmpfs', '/tmp', '--bind', $workspace, '/work',
                '--chdir', '/work', '/bin/sh', '-c', $script,
            ]);
            $process->setTimeout(45);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new RuntimeException('PDF rejected during isolated reconstruction.');
            }

            $result = $workspace.'/rebuilt.pdf';
            if (!is_file($result) || filesize($result) < 100 || filesize($result) > 12 * 1024 * 1024) {
                throw new RuntimeException('Reconstructed PDF invalid or too large.');
            }
            $extractedText = file_get_contents($workspace.'/extracted.txt');
            if ($extractedText === false || strlen($extractedText) > 200000) {
                throw new RuntimeException('CV text extraction unavailable or exceeds limits.');
            }
            $stream = fopen($result, 'rb');
            if (!is_resource($stream)) {
                throw new RuntimeException('Cannot read reconstructed PDF.');
            }
            try {
                if (!$disk->put($outputKey, $stream)) {
                    throw new RuntimeException('Cannot store reconstructed PDF.');
                }
            } finally {
                fclose($stream);
            }
            return $extractedText;
        } finally {
            foreach (glob($workspace.'/*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($workspace);
        }
    }
}
