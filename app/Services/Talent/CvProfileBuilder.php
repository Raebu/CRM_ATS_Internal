<?php

namespace App\Services\Talent;

use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class CvProfileBuilder
{
    public const KEYS = ['full_name','email','phone','location','professional_summary','employment','education','skills','qualifications','projects','languages'];

    /** Deterministic extraction: do not infer employment dates, qualifications or identity. */
    public function extract(string $text, ?string $knownName = null, ?string $knownEmail = null): array
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';
        $lines = preg_split('/\R/u', $text) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));
        $fields = array_fill_keys(self::KEYS, '');
        $fields['full_name'] = (string) $knownName;
        $fields['email'] = (string) $knownEmail;
        $emailMatches = [];
        preg_match('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', $text, $emailMatches);
        if (isset($emailMatches[0])) {
            $fields['email'] = $emailMatches[0];
        }
        $phoneMatches = [];
        preg_match('/(?<!\w)(?:\+?\d[\d .()\-]{8,}\d)/u', $text, $phoneMatches);
        if (isset($phoneMatches[0])) {
            $fields['phone'] = trim($phoneMatches[0]);
        }
        $sections = [
            'professional_summary' => '/^(profile|personal statement|summary|professional summary|about me)$/i',
            'employment' => '/^(work experience|employment|employment history|professional experience|career history)$/i',
            'education' => '/^(education|academic background|academic qualifications)$/i',
            'skills' => '/^(skills|key skills|technical skills|core skills)$/i',
            'qualifications' => '/^(qualifications|certifications|professional qualifications|certificates)$/i',
            'projects' => '/^(projects|selected projects)$/i',
            'languages' => '/^(languages|language skills)$/i',
        ];
        $section = null;
        foreach ($lines as $line) {
            if (strlen($line) > 2000) {
                continue;
            }
            $heading = trim($line, " \t:–—");
            $next = null;
            foreach ($sections as $key => $regex) {
                if (preg_match($regex, $heading)) {
                    $next = $key;
                    break;
                }
            }
            if ($next !== null) {
                $section = $next;
                continue;
            }
            if ($section !== null && strlen($fields[$section]) < 15000) {
                $fields[$section] .= ($fields[$section] === '' ? '' : "\n").$line;
            }
        }
        $flags = [];
        if (trim($text) === '') {
            $flags[] = 'No machine-readable text found (possibly scanned or image-only CV). Enter and verify details manually.';
        }
        foreach (['full_name','professional_summary','employment','education','skills'] as $required) {
            if (trim($fields[$required]) === '') {
                $flags[] = 'Review missing '.str_replace('_', ' ', $required).'.';
            }
        }
        $flags[] = 'All extracted details require human verification against candidate-provided information.';
        return [$fields, $flags];
    }

    /** The PDF contains only HTML-escaped approved structured fields. No source PDF bytes. */
    public function render(array $fields): string
    {
        $escaped = fn($s) => e((string) $s);
        $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;color:#182435;font-size:10pt;line-height:1.5;margin:36px}h1{font-size:25pt;color:#122947;margin:0 0 8px}h2{font-size:12pt;color:#1b4566;border-bottom:1px solid #aab8c6;margin-top:22px;padding-bottom:4px}.meta{font-size:9pt;color:#526473}.section{white-space:pre-wrap}</style></head><body>';
        $html .= '<h1>'.$escaped($fields['full_name'] ?? '').'</h1>';
        $meta = array_filter([$fields['email'] ?? '', $fields['phone'] ?? '', $fields['location'] ?? ''],fn($x)=>trim((string)$x)!=='');
        $html .= '<div class="meta">'.$escaped(implode(' | ', $meta)).'</div>';
        foreach (['professional_summary'=>'Professional profile','employment'=>'Employment history','education'=>'Education','skills'=>'Skills','qualifications'=>'Qualifications & certifications','projects'=>'Projects','languages'=>'Languages'] as $key=>$title) {
            if (trim((string)($fields[$key] ?? '')) !== '') {
                $html .= '<h2>'.$escaped($title).'</h2><div class="section">'.$escaped($fields[$key]).'</div>';
            }
        }
        $html .= '</body></html>';
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $output = $pdf->output();
        if (strlen($output) < 100 || strlen($output) > 12*1024*1024 || !str_starts_with($output, '%PDF-')) {
            throw new RuntimeException('Formatted CV generation failed.');
        }
        return $output;
    }
}
