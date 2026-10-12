<?php
namespace Tests\Feature\Talent;
use App\Models\Talent\{Tenant,Application,Candidate,Job,Offer};
use App\Services\Talent\OfferAcceptanceChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OfferAcceptanceChallengeTest extends TestCase {
 use RefreshDatabase;
 public function test_challenge_validation_fails_closed_when_acceptance_disabled():void {
  $this->assertFalse(app(OfferAcceptanceChallenge::class)->validateChallenge(1,1,str_repeat('A',64)));
  $this->assertFalse(app(OfferAcceptanceChallenge::class)->validateChallenge(1,1,'invalid'));
 }
 public function test_unverified_offer_challenge_issuance_is_disabled():void {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $this->assertFalse(config('talent.verified_offer_acceptance_enabled'));
  $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
  app(OfferAcceptanceChallenge::class)->issue($tenant->id,999999);
 }
}
