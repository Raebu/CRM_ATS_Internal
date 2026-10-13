<?php
namespace Tests\Unit\Talent;

use App\Services\Talent\ProductionReadiness;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
 public function test_preflight_reports_disallowed_production_flags():void
 {
  config()->set('talent.public_applications_enabled',true);
  config()->set('talent.external_email_enabled',true);
  config()->set('talent.verified_offer_acceptance_enabled',true);
  config()->set('talent.job_publishing_enabled',true);
  $checks=ProductionReadiness::check();
  $this->assertFalse($checks['public_intake_disabled']);
  $this->assertFalse($checks['external_email_disabled']);
  $this->assertFalse($checks['offer_acceptance_disabled']);
  $this->assertFalse($checks['publishing_disabled']);
 }
}
