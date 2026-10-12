<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('talent_offer_challenge_audit',function(Blueprint $t){$t->id();$t->unsignedBigInteger('tenant_id');$t->unsignedBigInteger('offer_id');$t->unsignedBigInteger('challenge_id');$t->string('action');$t->unsignedBigInteger('actor_id')->nullable();$t->timestamp('created_at');});}
 public function down():void {Schema::dropIfExists('talent_offer_challenge_audit');}
};
