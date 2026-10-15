<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('talent_cv_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('application_id')->index();
            $table->foreignId('candidate_id')->index();
            $table->foreignId('source_document_id')->nullable()->index();
            $table->string('status', 32)->default('review_required');
            $table->json('fields');
            $table->json('review_flags')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id','application_id','id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('talent_cv_profiles');
    }
};
