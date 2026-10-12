<?php
namespace Tests\Feature\Talent;

use Tests\TestCase;

class OfferReleaseGuardsTest extends TestCase
{
 public function test_offer_delivery_and_verified_acceptance_are_disabled_by_default():void
 {
  $this->assertFalse(config('talent.offer_delivery_enabled'));
  $this->assertFalse(config('talent.verified_offer_acceptance_enabled'));
  $this->assertFalse(config('talent.external_email_enabled'));
 }
}
