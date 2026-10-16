<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('talent_email_deliveries',function(Blueprint $t){
   $t->unsignedInteger('template_version')->nullable();
   $t->string('template_checksum',64)->nullable();
   $t->text('subject_snapshot')->nullable();
   $t->longText('body_snapshot')->nullable();
  });
 }
 public function down():void {
  Schema::table('talent_email_deliveries',function(Blueprint $t){
   $t->dropColumn(['template_version','template_checksum','subject_snapshot','body_snapshot']);
  });
 }
};