<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('talent_stage_history', function(Blueprint $t) {
   $t->id(); $t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('application_id')->constrained('talent_applications');
   $t->string('from_stage')->nullable(); $t->string('to_stage');
   $t->unsignedBigInteger('actor_id')->nullable(); $t->timestamps();
   $t->index(['tenant_id','application_id']);
  });
  Schema::create('talent_interviews',function(Blueprint $t) {
   $t->id(); $t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('application_id')->constrained('talent_applications');
   $t->string('title'); $t->dateTime('starts_at'); $t->dateTime('ends_at');
   $t->string('location')->nullable(); $t->string('status')->default('scheduled');
   $t->text('notes')->nullable(); $t->timestamps();
   $t->index(['tenant_id','starts_at']);
  });
  Schema::create('talent_offers',function(Blueprint $t) {
   $t->id(); $t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('application_id')->constrained('talent_applications');
   $t->unsignedInteger('version')->default(1); $t->string('status')->default('draft');
   $t->decimal('salary_amount',12,2)->nullable(); $t->char('currency',3)->default('GBP');
   $t->text('conditions')->nullable(); $t->timestamp('accepted_at')->nullable(); $t->timestamps();
   $t->unique(['application_id','version']); $t->index(['tenant_id','status']);
  });
 }
 public function down():void {
  Schema::dropIfExists('talent_offers'); Schema::dropIfExists('talent_interviews'); Schema::dropIfExists('talent_stage_history');
 }
};
