<?php
namespace Tests\Unit\Talent;

use App\Services\Talent\DetailedCvRenderer;
use App\Services\Talent\CvFieldSchema;
use Tests\TestCase;

final class DetailedCvRendererTest extends TestCase
{
    public function test_multiple_employment_and_education_items_produce_pdf(): void
    {
        $data=CvFieldSchema::defaults();
        $data['first_name']='Test';
        $data['last_name']='Applicant';
        $data['reverse_chronological']='yes';
        $data['employment_entries']=[
            ['company'=>'Earlier Company','title'=>'Engineer','start'=>'2019-01','end'=>'2021-02','duties'=>'Engineering'],
            ['company'=>'Latest Company','title'=>'Manager','start'=>'2022-03','present'=>'yes','achievements'=>'Saved 20 percent'],
        ];
        $data['education_entries']=[
            ['qualification'=>'BSc','institution'=>'Test University','graduation'=>'2017-06'],
            ['qualification'=>'MSc','institution'=>'Example College','graduation'=>'2018-06'],
        ];
        $pdf=(new DetailedCvRenderer())->render($data);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000,strlen($pdf));
    }
}
