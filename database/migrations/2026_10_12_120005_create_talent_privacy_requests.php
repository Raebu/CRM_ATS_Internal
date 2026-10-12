<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('talent_privacy_requests',function(Blueprint $t){
   $t->id();$t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('candidate_id')->constrained('talent_candidates');
   $t->string('request_type',30);$t->string('status',30)->default('pending_verification');
   $t->text('notes')->nullable();$t->unsignedBigInteger('created_by');
   $t->unsignedBigInteger('reviewed_by')->nullable();$t->timestamp('verified_at')->nullable();
   $t->timestamp('resolved_at')->nullable();$t->timestamps();
   $t->index(['tenant_id','status','created_at']);
  });
 }
 public function down():void {Schema::dropIfExists('talent_privacy_requests');}
};
