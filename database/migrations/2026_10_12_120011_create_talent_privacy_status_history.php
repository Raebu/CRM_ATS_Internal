<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::create('talent_privacy_status_history',function(Blueprint $t){
   $t->id();
   $t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('privacy_request_id')->constrained('talent_privacy_requests');
   $t->string('previous_status',40);
   $t->string('new_status',40);
   $t->unsignedBigInteger('actor_id')->nullable();
   $t->string('resolution_code',50)->nullable();
   $t->text('resolution_notes')->nullable();
   $t->timestamp('changed_at');
   $t->index(['tenant_id','privacy_request_id','changed_at'],'talent_privacy_history_lookup');
  });
 }
 public function down():void { Schema::dropIfExists('talent_privacy_status_history'); }
};