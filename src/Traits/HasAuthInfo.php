<?php

namespace Ugly\Base\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ugly\Base\Models\AuthInfo;

/**
 * Trait HasAuthInfo.
 *
 * @mixin Model
 */
trait HasAuthInfo
{
    /**
     * 定义密码字段.
     */
    protected static string $passwordField = 'password';

    /**
     * 认证信息.
     */
    public function authInfos(): MorphMany
    {
        return $this->morphMany(AuthInfo::class, 'authInfos', 'auth_type', 'auth_id');
    }
}
