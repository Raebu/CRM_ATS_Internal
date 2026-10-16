<?php
namespace Tests\Unit\Talent;

use App\Services\Talent\CvEvidenceReconciler;
use PHPUnit\Framework\TestCase;

final class CvEvidenceReconcilerTest extends TestCase
{
    public function test_supported_evidenced_fields_remain_unapproved(): void
    {
        $result=(new CvEvidenceReconciler())->reconcile('Lead Engineer at Example Ltd',[
            ['field'=>'professional_title','value'=>'Lead Engineer','source_excerpt'=>'Lead Engineer','page'=>1],
        ]);
        $this->assertSame('Lead Engineer',$result['fields']['professional_title']);
        $this->assertSame('review_required',$result['status']);
        $this->assertSame('review_required',$result['evidence']['professional_title'][0]['confidence']);
    }

    public function test_unsupported_or_unverifiable_fields_are_rejected(): void
    {
        $result=(new CvEvidenceReconciler())->reconcile('Engineering role',[
            ['field'=>'salary_expectation','value'=>'£200000','source_excerpt'=>'Engineering role','page'=>1],
            ['field'=>'professional_title','value'=>'CTO','source_excerpt'=>'CTO','page'=>1],
        ]);
        $this->assertSame('', $result['fields']['professional_title']);
        $this->assertCount(3,$result['review_flags']);
    }

    public function test_multiple_evidenced_jobs_are_retained_as_separate_unapproved_records(): void
    {
        $source="Engineer at Alpha 2020-2022. Manager at Beta 2022-Present.";
        $proposals=[
            ['field'=>'employment_entries','value'=>['company'=>'Alpha','title'=>'Engineer','start'=>'2020-01','end'=>'2022-01'],'source_excerpt'=>'Engineer at Alpha 2020-2022','page'=>1],
            ['field'=>'employment_entries','value'=>['company'=>'Beta','title'=>'Manager','start'=>'2022-02','present'=>'yes'],'source_excerpt'=>'Manager at Beta 2022-Present','page'=>1],
        ];
        $result=(new CvEvidenceReconciler())->reconcile($source,$proposals);
        $this->assertCount(2,$result['fields']['employment_entries']);
        $this->assertSame('Alpha',$result['fields']['employment_entries'][0]['company']);
        $this->assertSame('Beta',$result['fields']['employment_entries'][1]['company']);
        $this->assertSame('review_required',$result['status']);
    }

    public function test_duplicate_career_proposals_are_flagged(): void
    {
        $source='Engineer at Northstar 2020-2022';
        $proposal=['field'=>'employment_entries','value'=>['company'=>'Northstar','title'=>'Engineer','start'=>'2020-01'],'source_excerpt'=>$source,'page'=>1];
        $result=(new CvEvidenceReconciler())->reconcile($source,[$proposal,$proposal]);
        $this->assertCount(1,$result['fields']['employment_entries']);
        $this->assertContains('Duplicate employment_entries entry requires review.',$result['review_flags']);
    }

    public function test_conflicting_proposals_are_flagged_not_overwritten(): void
    {
        $result=(new CvEvidenceReconciler())->reconcile('Engineer Director',[
            ['field'=>'professional_title','value'=>'Engineer','source_excerpt'=>'Engineer','page'=>1],
            ['field'=>'professional_title','value'=>'Director','source_excerpt'=>'Director','page'=>1],
        ]);
        $this->assertSame('Engineer',$result['fields']['professional_title']);
        $this->assertContains('Conflicting extracted values for professional_title.',$result['review_flags']);
    }
}
