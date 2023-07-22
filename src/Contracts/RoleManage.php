<?php

namespace Ugly\Base\Contracts;

/**
 * 角色权限管理器.
 */
interface RoleManage
{
    /**
     * 获取角色权限类型.
     */
    public function getRoleBelongType(): string;

    /**
     * 获取角色权限所属ID.
     */
    public function getRoleBelongId(): int;
}
