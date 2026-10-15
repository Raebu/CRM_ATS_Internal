<?php
namespace App\Services\Talent;

use InvalidArgumentException;

/**
 * Accepts structured extraction proposals only with verifiable source excerpts.
 * It never makes an AI claim authoritative: every proposal stays review_required.
 */
final class CvEvidenceReconciler
{
    public function reconcile(string $sourceText, array $proposals): array
    {
        $fields = CvFieldSchema::defaults();
        $evidence = [];
        $flags = [];
        $allowed = [];
        foreach (CvFieldSchema::GROUPS as $group) $allowed += $group;
        $allowed += CvFieldSchema::SETTINGS;

        if (count($proposals) > 300) throw new InvalidArgumentException('Too many proposals.');
        foreach ($proposals as $i => $proposal) {
            if (!is_array($proposal)) continue;
            $key = $proposal['field'] ?? '';
            $value = $proposal['value'] ?? '';
            $quote = $proposal['source_excerpt'] ?? '';
            $page = $proposal['page'] ?? null;
            if (!is_string($key) || !array_key_exists($key,$allowed) ||
                !is_string($value) || !is_string($quote) ||
                strlen($value)>15000 || strlen($quote)>1500 || !is_int($page) || $page<1 || $page>100) {
                $flags[] = "Rejected unsupported or invalid extraction proposal #".($i+1);
                continue;
            }
            // A text match alone is necessary, not sufficient, to establish correctness.
            if ($quote === '' || !str_contains($sourceText,$quote)) {
                $flags[] = "Unverified source excerpt for {$key}; review manually.";
                continue;
            }
            if ($fields[$key] !== '' && $fields[$key] !== $value) {
                $flags[] = "Conflicting extracted values for {$key}.";
                continue;
            }
            $fields[$key] = $value;
            $evidence[$key][] = ['page'=>$page,'excerpt'=>$quote,'confidence'=>'review_required'];
        }
        $flags[] = 'AI suggestions are unverified; compare all fields, dates and record associations with the source.';
        return ['fields'=>$fields,'evidence'=>$evidence,'review_flags'=>$flags,'status'=>'review_required'];
    }
}
