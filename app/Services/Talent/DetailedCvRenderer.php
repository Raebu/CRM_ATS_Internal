<?php
namespace App\Services\Talent;

use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

final class DetailedCvRenderer
{
    public function render(array $fields): string
    {
        $escape=static fn($v)=>e((string)$v);
        $html='<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;color:#142539;font-size:10pt;line-height:1.4;margin:30px}h1{font-size:23pt;margin:0 0 8px}h2{font-size:12pt;border-bottom:1px solid #a3b4c1;margin-top:18px}p{white-space:pre-wrap;margin:5px 0}.detail{margin:12px 0;page-break-inside:avoid}.small{font-size:9pt;color:#526473}</style></head><body>';
        $html.='<h1>'.$escape(trim(($fields['first_name']??'').' '.($fields['last_name']??''))).'</h1>';
        $meta=[];
        foreach(['professional_title','email','phone','location','linkedin_url','portfolio_url'] as $f)
            if(trim((string)($fields[$f]??''))!=='')$meta[]=$fields[$f];
        $html.='<p class="small">'.$escape(implode(' | ',$meta)).'</p>';
        $section=static function(string $name,array $mapping)use(&$html,$fields,$escape):void{
            $parts=[];
            foreach($mapping as $field=>$label)
                if(trim((string)($fields[$field]??''))!=='') $parts[]='<p><strong>'.$escape($label).':</strong> '.$escape($fields[$field]).'</p>';
            if($parts)$html.='<h2>'.$escape($name).'</h2>'.implode('',$parts);
        };
        $section('Professional profile',[
            'years_experience'=>'Experience','expertise'=>'Expertise','achievement_headline'=>'Key achievement','career_objective'=>'Career objective']);
        foreach(CvFieldSchema::REPEATABLE as $field=>$group){
            $rows=$fields[$field]??[];
            if(!is_array($rows)||!count($rows))continue;
            if(($fields['reverse_chronological']??'')==='yes'){
                $dateField=$field==='employment_entries'?'start':'graduation';
                usort($rows,fn($a,$b)=>strcmp((string)($b[$dateField]??''),(string)($a[$dateField]??'')));
            }
            $html.='<h2>'.$escape($group['label']).'</h2>';
            foreach($rows as $row){
                if(!is_array($row))continue;
                $title=$field==='employment_entries'
                    ?trim(($row['title']??'').' - '.($row['company']??''))
                    :trim(($row['qualification']??'').' - '.($row['institution']??''));
                $html.='<div class="detail"><strong>'.$escape($title).'</strong>';
                if($field==='employment_entries'){
                    $html.='<p class="small">'.$escape(($row['start']??'').' to '.(($row['present']??'')==='yes'?'Present':($row['end']??''))).'</p>';
                }elseif(!empty($row['graduation'])){
                    $html.='<p class="small">'.$escape($row['graduation']).'</p>';
                }
                foreach($group['fields'] as $key=>$label){
                    if(in_array($key,['company','title','qualification','institution','start','end','graduation','present','expected'],true))continue;
                    if(trim((string)($row[$key]??''))!=='')$html.='<p><strong>'.$escape($label).':</strong> '.$escape($row[$key]).'</p>';
                }
                $html.='</div>';
            }
        }
        foreach(CvFieldSchema::GROUPS as $name=>$group){
            if(in_array($name,['Contact information','Personal profile / summary'],true))continue;
            $section($name,$group);
        }
        if(($fields['reference_placeholder']??'')==='yes')$html.='<h2>References</h2><p>Available upon request.</p>';
        $html.='</body></html>';
        $bytes=Pdf::loadHTML($html)->setPaper('a4')->output();
        if(!str_starts_with($bytes,'%PDF-')||strlen($bytes)>12*1024*1024||strlen($bytes)<100)
            throw new RuntimeException('Generated CV invalid.');
        return $bytes;
    }
}
