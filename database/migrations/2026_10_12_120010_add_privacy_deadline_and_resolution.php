<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {
  Schema::table('talent_privacy_requests',function(Blueprint $table){
   $table->timestamp('due_at')->nullable();
   $table->string('resolution_code',50)->nullable();
   $table->text('resolution_notes')->nullable();
   $table->timestamp('identity_evidence_reviewed_at')->nullable();
  });
 }
 public function down():void {
  Schema::table('talent_privacy_requests',function(Blueprint $table){
   $table->dropColumn(['due_at','resolution_code','resolution_notes','identity_evidence_reviewed_at']);
  });
 }
};