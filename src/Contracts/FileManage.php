<?php

namespace Ugly\Base\Contracts;

/**
 * 文件管理器.
 */
interface FileManage
{
    /**
     * 获取角色权限类型.
     */
    public function getFileBelongType(): string;

    /**
     * 获取角色权限所属ID.
     */
    public function getFileBelongId(): int;
}
