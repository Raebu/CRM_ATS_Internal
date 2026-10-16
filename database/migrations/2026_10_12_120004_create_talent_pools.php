<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('talent_pools',function(Blueprint $t){
   $t->id();$t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->string('name');$t->text('description')->nullable();$t->timestamps();
   $t->unique(['tenant_id','name']);
  });
  Schema::create('talent_pool_members',function(Blueprint $t){
   $t->id();$t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('pool_id')->constrained('talent_pools')->cascadeOnDelete();
   $t->foreignId('candidate_id')->constrained('talent_candidates')->cascadeOnDelete();
   $t->timestamp('added_at')->useCurrent();$t->timestamps();
   $t->unique(['pool_id','candidate_id']);$t->index(['tenant_id','candidate_id']);
  });
 }
 public function down():void {Schema::dropIfExists('talent_pool_members');Schema::dropIfExists('talent_pools');}
};
