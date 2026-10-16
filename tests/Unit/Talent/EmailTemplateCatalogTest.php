<?php
namespace Tests\Unit\Talent;
use App\Services\Talent\EmailTemplateCatalog;
use Tests\TestCase;
class EmailTemplateCatalogTest extends TestCase {
 public function test_templates_are_static_and_do_not_include_candidate_details():void {
  $template=EmailTemplateCatalog::get('application_acknowledgement');
  $this->assertSame('Your application has been received',$template['subject']);
  $this->assertStringNotContainsString('{{', $template['body']);
 }
 public function test_unknown_template_is_rejected():void {
  $this->expectException(\InvalidArgumentException::class);
  EmailTemplateCatalog::get('unknown');
 }
}
