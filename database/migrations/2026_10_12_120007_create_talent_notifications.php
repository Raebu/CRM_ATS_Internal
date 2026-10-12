<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('talent_notifications',function(Blueprint $t){
   $t->id();$t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->uuid('source_event_id');$t->string('kind',60);
   $t->string('status',30)->default('pending');
   $t->json('data')->nullable();
   $t->timestamp('acknowledged_at')->nullable();$t->timestamps();
   $t->unique(['source_event_id','kind']);
   $t->index(['tenant_id','status','created_at']);
  });
 }
 public function down():void {Schema::dropIfExists('talent_notifications');}
};
