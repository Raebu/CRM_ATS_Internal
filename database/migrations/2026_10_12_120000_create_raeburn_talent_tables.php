<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('talent_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('talent_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('talent_tenants')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
        });
        Schema::create('talent_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('talent_tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'email']);
        });
        Schema::create('talent_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('talent_tenants')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('talent_jobs')->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained('talent_candidates')->restrictOnDelete();
            $table->string('stage')->default('applied');
            $table->string('source')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tenant_id', 'job_id', 'candidate_id'], 'talent_application_unique');
            $table->index(['tenant_id', 'stage']);
        });
        Schema::create('talent_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('talent_tenants')->cascadeOnDelete();
            $table->uuid('event_id')->unique();
            $table->string('event_type');
            $table->unsignedSmallInteger('event_version')->default(1);
            $table->string('aggregate_type');
            $table->unsignedBigInteger('aggregate_id');
            $table->json('payload');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['published_at','created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('talent_events');
        Schema::dropIfExists('talent_applications');
        Schema::dropIfExists('talent_candidates');
        Schema::dropIfExists('talent_jobs');
        Schema::dropIfExists('talent_tenants');
    }
};
