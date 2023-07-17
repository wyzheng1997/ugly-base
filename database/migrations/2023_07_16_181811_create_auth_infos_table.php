<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth_infos', function (Blueprint $table) {
            $table->comment('账号认证信息');
            $table->id();
            $table->unsignedBigInteger('auth_id')->comment('认证账号ID');
            $table->string('auth_type')->comment('认证账号类型');
            $table->unsignedTinyInteger('type')->comment('授权类型');
            $table->string('token')->nullable()->comment('认证token/openid');
            $table->string('union_id')->nullable()->comment('认证union_id');
            $table->json('payload')->nullable()->comment('其他信息');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_infos');
    }
};
