<?php
namespace App\Services\Talent;

final class CvCoverageAuditor
{
    /**
     * Document text remains untrusted. Conservative token/line matching:
     * unknown lines are surfaced, never deleted or silently called captured.
     */
    public function audit(string $source, array $evidence): array
    {
        $used = [];
        foreach ($evidence as $entries) {
            foreach ((array)$entries as $entry) {
                if (is_array($entry) && isset($entry['excerpt']) && is_string($entry['excerpt'])) {
                    $used[] = trim($entry['excerpt']);
                }
            }
        }
        $unmapped = [];
        foreach (preg_split('/\R/u',$source) ?: [] as $line) {
            $line=trim($line);
            if ($line==='' || mb_strlen($line)<3) continue;
            $matched=false;
            foreach ($used as $excerpt) {
                if ($excerpt!=='' && (str_contains($excerpt,$line) || str_contains($line,$excerpt))) {
                    $matched=true; break;
                }
            }
            if (!$matched) $unmapped[]=$line;
            if (count($unmapped)>=500) break;
        }
        return [
            'unmapped_lines'=>$unmapped,
            'review_required'=>count($unmapped)>0,
            'note'=>'Coverage heuristic only. A zero count does not prove correct extraction.',
        ];
    }
}
