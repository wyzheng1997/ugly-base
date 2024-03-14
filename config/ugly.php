<?php

return [
    // 系统设置
    'config' => [
        'enable' => false,
        'cache_driver' => 'file', // null 表示不缓存
        'cache_key' => 'ugly-sys-config',
        'cache_ttl' => 0, // 缓存有效期单位秒 0表示永久缓存
    ],
    // 支付设置
    'payment' => [
        'enable' => true,
        'channel' => [
            // 支付渠道
        ],
    ],
];
