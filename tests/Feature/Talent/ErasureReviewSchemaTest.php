<?php
namespace Tests\Feature\Talent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
final class ErasureReviewSchemaTest extends TestCase {
 use RefreshDatabase;
 public function test_review_and_decision_tables_are_inert_and_present(): void {
  $this->assertTrue(Schema::hasTable('talent_erasure_reviews'));
  $this->assertTrue(Schema::hasTable('talent_erasure_decisions'));
  foreach (['tenant_id','privacy_request_id','candidate_id','status','prepared_by','approved_by','approved_at','inventory_digest','legal_hold_notes','backup_handling_notes'] as $column) {
   $this->assertTrue(Schema::hasColumn('talent_erasure_reviews',$column));
  }
  foreach (['review_id','data_category','proposed_action','legal_basis','rationale','reviewed_by','reviewed_at'] as $column) {
   $this->assertTrue(Schema::hasColumn('talent_erasure_decisions',$column));
  }
 }
}
