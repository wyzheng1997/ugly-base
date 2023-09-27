<?php

namespace Ugly\Base;

use Illuminate\Support\Fluent;
use Symfony\Component\HttpFoundation\Response;

class UglyBase
{
    /**
     * 上下文管理.
     */
    public static function context(): Fluent
    {
        return app('ugly.base.context');
    }

    /**
     * 设置失败响应业务状态码.
     */
    public static function setFailedResponse(array $config): void
    {
        self::context()->offsetSet('apiFailedResponse', $config);
    }

    /**
     * 获取失败响应业务状态码.
     */
    public static function getFailedResponse(): array
    {
        $config = self::context()->get('apiFailedResponse');

        return [
            'message' => data_get($config, 'message', 'failed'),
            'code' => data_get($config, 'code', 400),
            'httpCode' => data_get($config, 'httpCode', Response::HTTP_BAD_REQUEST),
        ];
    }
}
