<?php

// 权限角色主体配置.
$belongs_type = [
    'admin' => \App\Models\Admin::class, // 系统
];

// 权限配置.
$permissions = [
    [
        'name' => '系统管理',
        'slug' => 'system',
        'type' => 'admin',
        'children' => [
            ['name' => '管理员', 'slug' => 'system.admin'],
            ['name' => '角色管理', 'slug' => 'system.roles'],
            ['name' => '清理缓存', 'slug' => 'system.clear-cache'],
        ],
    ],
    [
        'name' => '开发配置',
        'slug' => 'develop',
        'type' => 'admin',
        'children' => [
            ['name' => '系统字典', 'slug' => 'develop.dict'],
            ['name' => '系统权限', 'slug' => 'develop.permission'],
        ],
    ],
];

return [
    'belongs_type' => $belongs_type,
    'permissions' => $permissions,
];
