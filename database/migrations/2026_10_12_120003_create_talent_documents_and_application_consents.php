<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('talent_documents',function(Blueprint $t){
   $t->id();$t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('candidate_id')->constrained('talent_candidates');
   $t->foreignId('application_id')->nullable()->constrained('talent_applications');
   $t->string('disk')->default('local');$t->string('storage_key')->unique();
   $t->string('original_name');$t->string('mime_type',100);
   $t->unsignedBigInteger('size_bytes');$t->string('scan_status')->default('pending');
   $t->timestamps();$t->index(['tenant_id','candidate_id']);
  });
  Schema::table('talent_applications',function(Blueprint $t){
   $t->string('privacy_notice_version')->nullable();
   $t->timestamp('privacy_acknowledged_at')->nullable();
  });
 }
 public function down():void {
  Schema::table('talent_applications',fn(Blueprint $t)=>$t->dropColumn(['privacy_notice_version','privacy_acknowledged_at']));
  Schema::dropIfExists('talent_documents');
 }
};
