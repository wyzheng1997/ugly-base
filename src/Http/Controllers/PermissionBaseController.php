<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Ugly\Base\Exceptions\ApiCustomError;
use Ugly\Base\Models\Permissions;
use Ugly\Base\Services\AuthInfoServices;
use Ugly\Base\Services\FormService;
use Ugly\Base\Traits\ApiResource;

/**
 * 权限基类.
 */
class PermissionBaseController extends Controller
{
    use ApiResource;

    /**
     * 认证守卫.
     */
    protected string $guard = '';

    /**
     * 权限列表.
     */
    public function index(): JsonResponse
    {
        $loginUser = AuthInfoServices::loginUser($this->guard);
        $permissions = Permissions::query()
            ->where('belongs_type', $loginUser->getRolePermissionType())
            ->orderBy('id')
            ->get();

        return $this->success(
            arr2tree($permissions, function ($item) {
                return $item->only(['id', 'name', 'slug', 'http_method', 'http_path']);
            })
        );
    }

    /**
     * 新增/更新表单配置.
     */
    protected function form(): FormService
    {
        $loginUser = AuthInfoServices::loginUser($this->guard);
        $belongs_type = $loginUser->getRolePermissionType();

        return FormService::make(Permissions::query(), function (FormService $form) use ($belongs_type) {
            $rules = ['name' => 'required'];
            // slug 唯一验证.
            $slugUnique = Rule::unique('permissions')
                ->where('belongs_type', $belongs_type);
            if ($form->isEdit()) {
                $slugUnique = $slugUnique->ignore($form->getModel());
            }
            $rules['slug'] = ['required', $slugUnique];
            $form->validate($rules);
            $form->extraFields(['pid' => 0, 'http_method', 'http_path']);
            $form->saving(function (FormService $form, $formData) use ($belongs_type) {
                if ($formData['pid'] > 0) {
                    $parent = Permissions::query()->find($formData['pid']);
                    if ($parent?->belongs_type != $belongs_type) {
                        throw new ApiCustomError('非法操作！');
                    }
                    if ($form->isEdit() && $parent->id === $form->getKey()) {
                        throw new ApiCustomError('上级权限不能是自己！');
                    }
                }
                $formData['belongs_type'] = $belongs_type;

                return $formData;
            });
        });
    }
}
