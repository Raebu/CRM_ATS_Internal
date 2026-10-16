<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('talent_events',function(Blueprint $table){
   $table->unsignedInteger('attempts')->default(0);
   $table->timestamp('next_attempt_at')->nullable();
   $table->timestamp('claimed_until')->nullable();
   $table->text('last_error')->nullable();
   $table->timestamp('dead_lettered_at')->nullable();
   $table->index(['published_at','dead_lettered_at','next_attempt_at'],'talent_outbox_ready_index');
  });
 }
 public function down():void {
  Schema::table('talent_events',function(Blueprint $table){
   $table->dropIndex('talent_outbox_ready_index');
   $table->dropColumn(['attempts','next_attempt_at','claimed_until','last_error','dead_lettered_at']);
  });
 }
};
