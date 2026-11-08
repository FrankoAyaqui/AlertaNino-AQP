<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('zones',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->text('description');$t->timestamps();});
  Schema::table('users',function(Blueprint $t){$t->string('role')->default('ciudadano');$t->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();});
  Schema::create('alerts',function(Blueprint $t){$t->id();$t->foreignId('zone_id')->constrained()->restrictOnDelete();$t->string('title');$t->string('level');$t->text('message');$t->string('source_url',1000);$t->dateTime('issued_at');$t->dateTime('expires_at');$t->string('status')->default('borrador');$t->timestamps();});
  Schema::create('guides',function(Blueprint $t){$t->id();$t->string('title');$t->text('body');$t->string('source_url',1000);$t->timestamps();});
  Schema::create('contacts',function(Blueprint $t){$t->id();$t->string('name');$t->string('phone',25);$t->string('source_url',1000);$t->timestamps();});
  Schema::create('notifications',function(Blueprint $t){$t->uuid('id')->primary();$t->string('type');$t->morphs('notifiable');$t->text('data');$t->timestamp('read_at')->nullable();$t->timestamps();});
 }
 public function down():void {Schema::dropIfExists('notifications');Schema::dropIfExists('contacts');Schema::dropIfExists('guides');Schema::dropIfExists('alerts');Schema::table('users',function(Blueprint $t){$t->dropConstrainedForeignId('zone_id');$t->dropColumn('role');});Schema::dropIfExists('zones');}
};
