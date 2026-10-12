<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('talent_email_deliveries',function(Blueprint $t){
   $t->id();
   $t->foreignId('tenant_id')->constrained('talent_tenants');
   $t->foreignId('notification_id')->constrained('talent_notifications');
   $t->string('template_key',100);
   $t->string('status',30)->default('draft');
   $t->string('recipient_email',255)->nullable();
   $t->timestamp('approved_at')->nullable();
   $t->unsignedBigInteger('approved_by')->nullable();
   $t->timestamp('sent_at')->nullable();
   $t->text('last_error')->nullable();
   $t->timestamps();
   $t->unique(['notification_id','template_key'],'talent_delivery_unique');
   $t->index(['tenant_id','status']);
  });
 }
 public function down():void { Schema::dropIfExists('talent_email_deliveries'); }
};
