<?php

namespace App\Services\Talent;

use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class CvProfileBuilder
{
    public const KEYS = ['full_name','email','phone','location','professional_summary','employment','education','skills','qualifications','projects','languages','additional_information'];

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
            $target = $section ?? 'additional_information';
            if (strlen($fields[$target]) < 15000) {
                $fields[$target] .= ($fields[$target] === '' ? '' : "\n").$line;
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
        $structured=CvFieldSchema::defaults();
        $name=explode(' ',trim($fields['full_name']),2);
        $structured['first_name']=$name[0]??'';
        $structured['last_name']=$name[1]??'';
        foreach(['email','phone','location','languages'] as $key) $structured[$key]=$fields[$key];
        $structured['career_objective']=$fields['professional_summary'];
        $structured['technical_skills']=$fields['skills'];
        $structured['industry_certifications']=$fields['qualifications'];
        $structured['additional_information']=trim(implode("\n",array_filter([
            $fields['employment'],$fields['education'],$fields['projects'],$fields['additional_information']
        ])));

        // Only split clearly labelled rows. Ambiguous histories remain in the
        // original extracted text for human verification rather than guessing.
        $structured['employment_entries'] = $this->employmentEntries($fields['employment']);
        $structured['education_entries'] = $this->educationEntries($fields['education']);
        if ($fields['employment'] !== '' && $structured['employment_entries'] === []) {
            $flags[] = 'Employment entries require manual separation.';
        }
        if ($fields['education'] !== '' && $structured['education_entries'] === []) {
            $flags[] = 'Education entries require manual separation.';
        }
        return [$structured,$flags];
    }


    /**
     * Conservative parsing of unambiguous "Role - Organisation" rows.
     * Preserve the full source section in additional_information for review.
     */
    private function employmentEntries(string $section): array
    {
        $entries = [];
        $current = null;
        foreach (preg_split('/\R/u', $section) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (!preg_match('/^[A-Za-z]+\s+\d{4}\s+[-–—]\s+(?:Present|Current|[A-Za-z]+\s+\d{4})$/iu', $line)
                && preg_match('/^(.{2,120}?)\s+[-–—]\s+(.{2,120})$/u', $line, $match)) {
                if ($current !== null) $entries[] = $current;
                $current = ['title' => trim($match[1]), 'company' => trim($match[2])];
                continue;
            }
            if ($current === null) continue;
            if (preg_match('/^([A-Za-z]+)\s+(\d{4})\s+[-–—]\s+(Present|Current|([A-Za-z]+)\s+(\d{4}))$/iu', $line, $match)) {
                $start = $this->month($match[1], $match[2]);
                $end = strtolower($match[3]) === 'present' || strtolower($match[3]) === 'current'
                    ? null : $this->month($match[4] ?? '', $match[5] ?? '');
                if ($start !== null) $current['start'] = $start;
                if (strtolower($match[3]) === 'present' || strtolower($match[3]) === 'current') {
                    $current['present'] = 'yes';
                } elseif ($end !== null) {
                    $current['end'] = $end;
                }
            } else {
                $current['duties'] = trim(($current['duties'] ?? '')."\n".$line);
            }
        }
        if ($current !== null) $entries[] = $current;
        return array_slice($entries, 0, 100);
    }

    private function educationEntries(string $section): array
    {
        $entries = [];
        $current = null;
        foreach (preg_split('/\R/u', $section) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^(.{2,150}?)\s+[-–—]\s+(.{2,150})$/u', $line, $match)) {
                if ($current !== null) $entries[] = $current;
                $current = ['qualification' => trim($match[1]), 'institution' => trim($match[2])];
            } elseif ($current !== null && preg_match('/^(19|20)\d{2}$/', $line)) {
                // Year-only precision must not be represented as a fabricated month.
                $current['coursework'] = trim(($current['coursework'] ?? '')."\nGraduation year: ".$line);
            } elseif ($current !== null) {
                $current['coursework'] = trim(($current['coursework'] ?? '')."\n".$line);
            }
        }
        if ($current !== null) $entries[] = $current;
        return array_slice($entries, 0, 100);
    }

    private function month(string $name, string $year): ?string
    {
        $months = ['january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,
            'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12];
        $number = $months[strtolower($name)] ?? null;
        return $number === null ? null : sprintf('%04d-%02d', (int) $year, $number);
    }

    /** The PDF contains only HTML-escaped approved structured fields. No source PDF bytes. */
    public function render(array $fields): string
    {
        $escaped = fn($s) => e((string) $s);
        $html = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;color:#182435;font-size:10pt;line-height:1.5;margin:36px}h1{font-size:25pt;color:#122947;margin:0 0 8px}h2{font-size:12pt;color:#1b4566;border-bottom:1px solid #aab8c6;margin-top:22px;padding-bottom:4px}.meta{font-size:9pt;color:#526473}.section{white-space:pre-wrap}</style></head><body>';
        $html .= '<h1>'.$escaped($fields['full_name'] ?? '').'</h1>';
        $meta = array_filter([$fields['email'] ?? '', $fields['phone'] ?? '', $fields['location'] ?? ''],fn($x)=>trim((string)$x)!=='');
        $html .= '<div class="meta">'.$escaped(implode(' | ', $meta)).'</div>';
        foreach (['professional_summary'=>'Professional profile','employment'=>'Employment history','education'=>'Education','skills'=>'Skills','qualifications'=>'Qualifications & certifications','projects'=>'Projects','languages'=>'Languages','additional_information'=>'Additional verified information'] as $key=>$title) {
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
