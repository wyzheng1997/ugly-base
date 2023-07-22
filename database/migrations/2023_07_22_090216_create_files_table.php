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
        Schema::create('files', function (Blueprint $table) {
            $table->comment('文件资源表');
            $table->id();
            $table->string('name')->comment('文件名');
            $table->string('path')->comment('文件路径');
            $table->string('type')->comment('文件类型');
            $table->string('size')->comment('文件大小');
            $table->string('sha1')->index()->comment('文件sha1');
            $table->string('belong_type')->comment('上传者类型');
            $table->unsignedBigInteger('belong_id')->comment('上传者id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
