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
        foreach (CvFieldSchema::REPEATABLE as $collection => $definition) {
            $records = [];
            $seen = [];
            foreach ($proposals as $proposal) {
                if (!is_array($proposal) || ($proposal['field']??'')!==$collection) continue;
                $entry = $proposal['value'] ?? null;
                $quote = $proposal['source_excerpt'] ?? '';
                $page = $proposal['page'] ?? null;
                if (!is_array($entry) || !is_string($quote) || $quote==='' ||
                    !str_contains($sourceText,$quote) || !is_int($page) || $page<1 || $page>100 ||
                    count($records)>=100) {
                    $flags[]="Invalid or unsupported {$collection} proposal.";
                    continue;
                }
                $record=[];
                foreach ($definition['fields'] as $name=>$label) {
                    $v=$entry[$name]??'';
                    if (!is_string($v) || strlen($v)>15000) {
                        $flags[]="Invalid {$collection}.{$name}; requires review.";
                        $v='';
                    }
                    $record[$name]=$v;
                }
                if (trim(($record['company']??'').($record['title']??'').($record['qualification']??'').($record['institution']??''))==='') {
                    $flags[]="Empty {$collection} entry ignored.";
                    continue;
                }
                $identity = implode('|', array_map(fn($v)=>mb_strtolower(trim((string)$v)), [$record['company']??$record['institution']??'', $record['title']??$record['qualification']??'', $record['start']??$record['graduation']??'']));
                if (isset($seen[$identity])) {
                    $flags[]="Duplicate {$collection} entry requires review.";
                    continue;
                }
                $seen[$identity]=true;
                $records[]=$record;
                $evidence[$collection][]=['page'=>$page,'excerpt'=>$quote,'confidence'=>'review_required'];
            }
            $fields[$collection]=$records;
        }
        $flags[] = 'AI suggestions are unverified; compare all fields, dates and record associations with the source.';
        return ['fields'=>$fields,'evidence'=>$evidence,'review_flags'=>$flags,'status'=>'review_required'];
    }
}
