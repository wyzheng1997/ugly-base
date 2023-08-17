<?php

namespace Ugly\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Ugly\Base\Enums\ApiResponseCode;
use Ugly\Base\Traits\ApiResource;

/**
 * 权限守卫中间件.
 */
class PermissionGuard
{
    use ApiResource;

    public function handle(Request $request, Closure $next, $guard): Response
    {
        // 获取登录用户
        $user = $request->user($guard);

        $method = strtoupper($request->method());
        $path = $request->path();
        // 确保path以/开头
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        // 检查权限(白名单)
        if (
            method_exists($user, 'getPermissionWhiteList') &&
            $this->checkPermission($method, $path, $user->getPermissionWhiteList())
        ) {
            return $next($request);
        }

        // 检查权限(数据库权限)
        if (
            method_exists($user, 'getPermissionRouteRules') &&
            $this->checkPermission($method, $path, $user->getPermissionRouteRules())
        ) {
            return $next($request);
        }

        return $this->failed('暂无权限', ApiResponseCode::Forbidden, Response::HTTP_FORBIDDEN);
    }

    /**
     * 检查权限.
     */
    protected function checkPermission(string $method, string $path, array $rules): bool
    {
        foreach ($rules as $rule) {
            // 解析规则 [方法:路径]
            [$rule_method, $rule_path] = explode(':', $rule);
            $rule_method = strtoupper($rule_method);
            // 检查方法是否匹配
            if ($rule_method !== 'ANY' && $rule_method !== $method) {
                continue;
            }
            // 正则检查路径是否匹配
            if (preg_match('/^'.str_replace('/', '\/', $rule_path).'$/', $path)) {
                return true;
            }
        }

        return false;
    }
}
