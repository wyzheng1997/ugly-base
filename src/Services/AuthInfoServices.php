<?php

namespace Ugly\Base\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Ugly\Base\Models\AuthInfo;

class AuthInfoServices
{
    protected string $model;

    public function __construct(
        protected string $guard,
        protected int $type,
        protected string $token,
        protected string $unionId = '',
        protected array $payload = []
    ) {
        $guard_config = config('auth.guards.'.$guard);
        $provider_config = config('auth.providers.'.$guard_config['provider']);
        $this->model = $provider_config['model'];
    }

    public static function make(
        string $guard,
        int $type,
        string $token,
        string $unionId = '',
        array $payload = []
    ): self {
        return new static($guard, $type, $token, $unionId, $payload);
    }

    /**
     * 通过token获取账号认证信息.
     */
    public function firstByToken(): Model|Builder|null
    {
        if ($this->token) {
            return AuthInfo::query()
                ->where('auth_type', $this->model)
                ->where('type', $this->type)
                ->where('token', $this->token)
                ->first();
        }

        return null;
    }

    /**
     * 通过unionId获取账号认证信息.
     */
    public function firstByUnionId(): Model|Builder|null
    {
        if ($this->unionId) {
            return AuthInfo::query()
                ->where('auth_type', $this->model)
                ->where('union_id', $this->unionId)
                ->first();
        }

        return null;
    }

    /**
     * 获取账号认证信息（先尝试使用token，查询不到再使用union_id）.
     */
    public function first(): Model|Builder|null
    {
        $authInfo = $this->firstByToken();
        if (! $authInfo && $this->unionId) {
            $authInfo = $this->firstByUnionId();
        }

        return $authInfo;
    }

    /**
     * 检查或创建账号认证信息.
     */
    public function checkOrCreate(array $account = [], \Closure $callback = null): Model|Builder|null
    {
        $lock = Cache::lock('auth_info:'.$this->token, 5); // 获取锁
        if (! $lock->get()) { // 获取锁失败
            abort(Response::HTTP_TOO_MANY_REQUESTS, '操作过于频繁，请稍后再试');
        }

        // 获取锁成功, 处理业务逻辑 >>>>>
        $tokenAuthInfo = $this->firstByToken();
        if (! $tokenAuthInfo) { // 不存在token认证信息
            $unionIdAuthInfo = $this->firstByUnionId();
            $isNewAccount = false; // 是否是新账号
            if ($unionIdAuthInfo) {
                $account = $unionIdAuthInfo->account;
            } else { // 不存在token和union_id认证信息，表示账号不存在
                $account = $this->model::query()->create($account); // 创建账号
                $isNewAccount = true;
            }

            // 创建token认证信息
            $tokenAuthInfo = $account->authInfos()->firstOrcreate([
                'auth_type' => $this->model,
                'type' => $this->type,
                'token' => $this->token,
            ], [
                'union_id' => $this->unionId ?: null,
                'payload' => $this->payload,
            ]);

            if ($callback instanceof \Closure) {
                call_user_func($callback, $isNewAccount, $account);
            }
        }
        $tokenAuthInfo->load('account');

        // 释放锁
        $lock->release();

        return $tokenAuthInfo;
    }

    /**
     * 获取登录用户，如果没有登录，抛出异常.
     *
     * @throws HttpException
     */
    public static function loginUser(string $guard): Authenticatable
    {
        $user = self::tryLoginUser($guard);
        if ($user) {
            return $user;
        }
        abort(Response::HTTP_UNAUTHORIZED, '请先登录！');
    }

    /**
     * 尝试获取登录用户，如果没有登录，返回null.
     */
    public static function tryLoginUser(string $guard): ?Authenticatable
    {
        if (auth($guard)->check()) {
            return auth($guard)->user();
        }

        return null;
    }
}
