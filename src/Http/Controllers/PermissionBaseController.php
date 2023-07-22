<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Ugly\Base\Exceptions\ApiCustomError;
use Ugly\Base\Models\Permissions;
use Ugly\Base\Services\FormService;

/**
 * 权限基类.
 */
class PermissionBaseController extends QuickFormController
{
    /**
     * 权限类型.
     */
    protected string $type = '';

    /**
     * 权限列表.
     */
    public function index(): JsonResponse
    {
        $permissions = Permissions::query()
            ->where('belongs_type', $this->type)
            ->orderBy('id')
            ->get();

        return $this->success(
            arr2tree($permissions, function ($item) {
                return $item->only(['id', 'name', 'slug', 'http_method', 'http_path', 'pid']);
            })
        );
    }

    /**
     * 新增/更新表单配置.
     */
    protected function form(): FormService
    {
        $model = Permissions::query()->where('belongs_type', $this->type);

        return FormService::make($model, function (FormService $form) {
            $rules = ['name' => 'required'];
            // slug 唯一验证.
            $slugUnique = Rule::unique('permissions')
                ->where('belongs_type', $this->type);
            if ($form->isEdit()) {
                $slugUnique = $slugUnique->ignore($form->getModel());
            }
            $rules['slug'] = ['required', $slugUnique];
            $form->validate($rules);
            $form->extraFields(['pid' => 0, 'http_method', 'http_path']);

            // 保存时，验证上级权限是否合法.
            $form->saving(function (FormService $form, $formData) {
                if ($formData['pid'] > 0) {
                    $parent = Permissions::query()->find($formData['pid']);
                    if ($parent?->belongs_type != $this->type) {
                        throw new ApiCustomError('非法操作！');
                    }
                    if ($form->isEdit() && $parent->id === (int) $form->getKey()) {
                        throw new ApiCustomError('上级权限不能是自己！');
                    }
                }
                $formData['belongs_type'] = $this->type;

                return $formData;
            });

            // 删除权限时，删除角色权限关联.
            $form->deleted(function (FormService $form) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $form->getKey())
                    ->delete();
            });
        });
    }
}
