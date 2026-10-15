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
