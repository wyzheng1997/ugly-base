<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Ugly\Base\Models\Permissions;
use Ugly\Base\Services\AuthInfoServices;
use Ugly\Base\Traits\ApiResource;

/**
 * 权限列表.
 */
class PermissionController extends Controller
{
    use ApiResource;

    /**
     * 权限列表.
     */
    public function index($guard): JsonResponse
    {
        $loginUser = AuthInfoServices::loginUser($guard);
        $permissions = Permissions::query()
            ->where('belongs_type', $loginUser->getRolePermissionType())
            ->orderBy('id')
            ->get();

        return $this->success(
            arr2tree($permissions, function ($item) {
                return $item->only(['id', 'name', 'slug']);
            })
        );
    }
}
