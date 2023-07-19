<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Ugly\Base\Exceptions\ApiCustomError;
use Ugly\Base\Models\Permissions;
use Ugly\Base\Models\Role;
use Ugly\Base\Services\AuthInfoServices;
use Ugly\Base\Services\FormService;

/**
 * 角色管理.
 */
class RoleController extends QuickFormController
{
    /**
     * 认证守卫.
     */
    protected string $guard = '';

    /**
     * 角色列表.
     */
    public function index(): JsonResponse
    {
        $loginUser = AuthInfoServices::loginUser($this->guard);

        return $this->success(
            Role::query()
                ->where('belongs_type', $loginUser->getRolePermissionType())
                ->where('belongs_id', $loginUser->getRoleBelongId())
                ->orderByDesc('id')
                ->get(['id', 'name', 'slug'])
        );
    }

    /**
     * 新增/更新表单配置.
     */
    protected function form(): FormService
    {
        $guard = $this->guard;

        return FormService::make(Role::class, function (FormService $form) use ($guard) {
            $loginUser = AuthInfoServices::loginUser($guard);
            $belongs_type = $loginUser->getRolePermissionType();
            $belongs_id = $loginUser->getRoleBelongId();
            $rules = [
                'name' => 'required',
                'permissions' => 'array',
            ];
            // slug 唯一验证.
            $slugUnique = Rule::unique('roles')
                ->where('belongs_type', $belongs_type)
                ->where('belongs_id', $belongs_id);
            if ($form->isEdit()) {
                $slugUnique = $slugUnique->ignore($form->getModel());
            }
            $rules['slug'] = ['required', $slugUnique];
            $form->validate($rules);

            // 保存前回调.
            $form->saving(function (FormService $form, $formData) use ($belongs_type, $belongs_id) {
                if ($form->isEdit() && $form->getModel()->belongs_id != $belongs_id) {
                    throw new ApiCustomError('无权修改此角色');
                }

                $formData['belongs_type'] = $belongs_type;
                $formData['belongs_id'] = $belongs_id;

                unset($formData['permissions']);

                return $formData;
            });

            // 保存后处理权限关联关系.
            $form->saved(function (FormService $form, Request $request) use ($belongs_type) {
                $permission_ids = $request->input('permissions', []);
                $form->getModel()->permissions()->sync(
                    Permissions::query()
                        ->where('belongs_type', $belongs_type)
                        ->whereIn('id', $permission_ids)
                        ->pluck('id')
                );
            });

            // 删除后清空关联关系.
            $form->deleted(function (FormService $form) {
                $id = $form->getKey();
                DB::table('role_assignments')->where('role_id', $id)->delete();
                DB::table('role_has_permissions')->where('role_id', $id)->delete();
            });

        });
    }
}
