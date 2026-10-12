<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::create('talent_offer_acceptance_challenges',function(Blueprint $t){
   $t->id();
   $t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('offer_id')->constrained('talent_offers');
   $t->string('token_hash',64)->unique();
   $t->timestamp('expires_at');
   $t->timestamp('consumed_at')->nullable();
   $t->timestamp('revoked_at')->nullable();
   $t->unsignedBigInteger('issued_by')->nullable();
   $t->timestamps();
   $t->index(['tenant_id','offer_id','expires_at'],'talent_offer_challenge_lookup');
  });
 }
 public function down():void {Schema::dropIfExists('talent_offer_acceptance_challenges');}
};