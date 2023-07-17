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
        Schema::create('admins', function (Blueprint $table) {
            $table->comment('管理员表');
            $table->id();
            $table->string('name')->comment('管理员名称');
            $table->string('password')->unique()->comment('密码');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->comment('角色表');
            $table->id();
            $table->string('name')->comment('角色名称');
            $table->string('slug')->comment('角色标识');
            $table->string('belongs_type')->comment('所属类型');
            $table->unsignedBigInteger('belongs_id')->comment('所属ID');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->comment('权限表');
            $table->id();
            $table->string('name')->comment('权限名称');
            $table->string('slug')->comment('权限标识');
            $table->unsignedBigInteger('pid')->comment('父级ID');
            $table->string('belongs_type')->comment('所属类型');
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->comment('角色权限关联表');
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('permission_id')->comment('权限ID');
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('account_has_roles', function (Blueprint $table) {
            $table->comment('账号角色关联表');
            $table->unsignedBigInteger('belongs_id')->comment('所属ID');
            $table->string('belongs_type')->comment('所属类型');
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unique(['role_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('account_has_permissions');
    }
};
