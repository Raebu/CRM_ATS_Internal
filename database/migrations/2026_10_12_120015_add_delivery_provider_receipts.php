<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('talent_email_deliveries',function(Blueprint $t){
   $t->string('provider_message_id',200)->nullable();
   $t->timestamp('submitted_at')->nullable();
  });
 }
 public function down():void {
  Schema::table('talent_email_deliveries',function(Blueprint $t){$t->dropColumn(['provider_message_id','submitted_at']);});
 }
};