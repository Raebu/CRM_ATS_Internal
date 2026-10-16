<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('talent_placements',function(Blueprint $t){
   $t->id();$t->unsignedBigInteger('tenant_id');$t->unsignedBigInteger('application_id');
   $t->date('start_date');$t->unsignedBigInteger('recruiter_id');
   $t->string('status',30)->default('onboarding');$t->text('handover_notes')->nullable();
   $t->timestamps();$t->unique(['tenant_id','application_id']);
  });
  Schema::create('talent_placement_tasks',function(Blueprint $t){
   $t->id();$t->unsignedBigInteger('tenant_id');$t->unsignedBigInteger('placement_id');
   $t->string('task_key',60);$t->string('title',180);
   $t->timestamp('completed_at')->nullable();$t->unsignedBigInteger('completed_by')->nullable();
   $t->timestamps();$t->unique(['placement_id','task_key']);
  });
 }
 public function down():void {Schema::dropIfExists('talent_placement_tasks');Schema::dropIfExists('talent_placements');}
};