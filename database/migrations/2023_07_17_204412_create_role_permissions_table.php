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
        Schema::create('roles', function (Blueprint $table) {
            $table->comment('角色表');
            $table->id();
            $table->string('name')->comment('角色名称');
            $table->string('slug')->comment('角色标识');
            $table->string('belongs_type')->comment('所属类型');
            $table->unsignedBigInteger('belongs_id')->comment('所属ID');
            $table->unique(['slug', 'belongs_type', 'belongs_id'], 'slug_type_unique');
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
            $table->unique(['slug', 'belongs_type'], 'slug_type_unique');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->comment('角色权限关联表');
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('permission_id')->comment('权限ID');
            $table->unique(['role_id', 'permission_id'], 'role_permission_unique');
        });

        Schema::create('role_assignments', function (Blueprint $table) {
            $table->comment('角色分配表');
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('belongs_id')->comment('所属ID');
            $table->string('belongs_type')->comment('所属类型');
            $table->unique(['role_id', 'belongs_type', 'belongs_id'], 'role_unique');
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
        Schema::dropIfExists('role_assignments');
    }
};
