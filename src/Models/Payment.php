<?php

namespace Ugly\Base\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Ugly\Base\Casts\Amount;
use Ugly\Base\Enums\PaymentStatus;
use Ugly\Base\Enums\PaymentType;

class Payment extends Model
{
    protected $table = 'payments';

    protected $guarded = [];

    protected $casts = [
        'amount' => Amount::class,
        'status' => PaymentStatus::class,
        'type' => PaymentType::class,
        'notification_data' => 'json',
        'attach' => 'json',
    ];

    // 生成唯一号.
    public static function generateNo(): string
    {
        return date('YmdHis').explode('.', microtime(true))[1].random_int(1000, 9999);
    }

    /**
     * 创建付款.
     *
     * @param  int  $channel 支付通道.
     * @param  float  $amount 支付金额/分.
     * @param  string  $job 成功后需要执行的任务.
     * @param  Carbon|string|null  $expire_at 过期时间.
     */
    public function createPay(int $channel, float $amount, string $job, Carbon|string $expire_at = null): Model|Builder
    {
        return self::query()->create([
            'no' => self::generateNo(),
            'channel' => $channel,
            'amount' => $amount,
            'type' => PaymentType::Pay,
            'status' => PaymentStatus::Processing,
            'job' => $job,
            'expire_at' => $expire_at,
        ]);
    }

    /**
     * 对应的商户.
     */
    public function merchant(): MorphTo
    {
        return $this->morphTo();
    }
}
