<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('talent_erasure_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('talent_tenants')->restrictOnDelete();
            $t->foreignId('privacy_request_id')->constrained('talent_privacy_requests')->restrictOnDelete();
            $t->foreignId('candidate_id')->constrained('talent_candidates')->restrictOnDelete();
            $t->string('status', 32)->default('draft');
            $t->unsignedBigInteger('prepared_by');
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->string('inventory_digest', 64)->nullable();
            $t->text('legal_hold_notes')->nullable();
            $t->text('backup_handling_notes')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id','privacy_request_id']);
        });
        Schema::create('talent_erasure_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('talent_tenants')->restrictOnDelete();
            $t->foreignId('review_id')->constrained('talent_erasure_reviews')->restrictOnDelete();
            $t->string('data_category', 64);
            $t->string('proposed_action', 32)->default('review_required');
            $t->string('legal_basis', 128)->nullable();
            $t->text('rationale')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['review_id','data_category']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('talent_erasure_decisions');
        Schema::dropIfExists('talent_erasure_reviews');
    }
};
