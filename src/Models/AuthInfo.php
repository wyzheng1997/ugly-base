<?php

namespace Ugly\Base\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 账号认证信息.
 */
class AuthInfo extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'json',
    ];

    /**
     * 关联账号.
     */
    public function account(): MorphTo
    {
        return $this->morphTo('account', 'auth_type', 'auth_id');
    }
}
