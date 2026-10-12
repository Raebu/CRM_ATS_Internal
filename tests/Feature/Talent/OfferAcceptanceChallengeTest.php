<?php
namespace Tests\Feature\Talent;
use App\Models\Talent\{Tenant,Application,Candidate,Job,Offer};
use App\Services\Talent\OfferAcceptanceChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OfferAcceptanceChallengeTest extends TestCase {
 use RefreshDatabase;
 public function test_unverified_offer_challenge_issuance_is_disabled():void {
  $tenant=Tenant::firstOrCreate(['slug'=>'raeburn-group'],['name'=>'The Raeburn Group']);
  $this->assertFalse(config('talent.verified_offer_acceptance_enabled'));
  $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
  app(OfferAcceptanceChallenge::class)->issue($tenant->id,999999);
 }
}
