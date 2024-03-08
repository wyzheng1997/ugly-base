<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('sys_configs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->comment('唯一标识符');
            $table->longText('value')->nullable()->comment('值');
            $table->string('desc')->nullable()->comment('描述');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_configs');
    }
};