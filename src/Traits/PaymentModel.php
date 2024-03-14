<?php

namespace Ugly\Base\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Ugly\Base\Enums\PaymentStatus;
use Ugly\Base\Enums\PaymentType;
use Ugly\Base\Exceptions\ApiCustomError;

/**
 *  统一支付.
 *
 * @mixin Model
 */
trait PaymentModel
{
    /**
     * 生成唯一号.
     */
    protected static function generateNo(): string
    {
        [$m, $s] = explode(' ', microtime());

        return date('ymdHis', $s).substr($m, 2).rand(10, 99);
    }

    /**
     * 获取支付通道类.
     *
     * @param  string  $channel 支付通道
     *
     * @throws ApiCustomError
     */
    private static function getChannelClass(string $channel): string
    {
        if (class_exists($channel)) {
            return $channel;
        } else {
            $channel_class = config('ugly.payment.channel.'.$channel);
            if ($channel_class && class_exists($channel_class)) {
                return $channel_class;
            }
            throw new ApiCustomError('支付通道不存在:'.$channel);
        }
    }

    /**
     * 默认创建.
     *
     * @param  Model|Builder|null  $merchant 商户.
     */
    private static function defaultCreate(array $data, Model|Builder $merchant = null): Model|Builder
    {
        $default = [
            'no' => self::generateNo(),
            'status' => PaymentStatus::Processing,
        ];
        if ($merchant) {
            $default['merchant_id'] = $merchant->getKey();
            $default['merchant_type'] = $merchant->getMorphClass();
        }

        return self::query()->create(array_merge($default, $data));
    }

    /**
     * 创建付款.
     *
     * @param  string  $channel 支付通道.
     * @param  float  $amount 支付金额/元.
     * @param  string  $job 成功后需要执行的任务.
     * @param  Carbon|string|null  $expire_at 过期时间.
     * @param  string|null  $order_no 内部订单号.
     * @param  array  $attach 附加信息.
     * @param  Model|Builder|null  $merchant 商户.
     */
    public static function pay(string $channel, float $amount, string $job, string $order_no = null, array $attach = [], Carbon|string $expire_at = null, Model|Builder $merchant = null): Model|Builder
    {
        $data = compact('channel', 'amount', 'job', 'expire_at', 'order_no', 'attach');
        $data['type'] = PaymentType::Pay;

        return self::defaultCreate($data, $merchant);
    }

    /**
     * 支付单退款.
     *
     * @param  float  $amount 退款金额/元.
     * @param  string  $job 退款后需要执行的任务.
     * @param  array  $attach 附加信息.
     * @param  Model|Builder|null  $merchant 商户.
     *
     * @throws ApiCustomError
     */
    public function refund(float $amount, string $job = '', array $attach = [], Model|Builder $merchant = null): Model|Builder
    {
        if (
            $this->getAttribute('status') !== PaymentStatus::Success ||
            $this->getAttribute('type') !== PaymentType::Pay
        ) {
            throw new ApiCustomError('支付单状态不正确！');
        }

        $data = compact('amount', 'job', 'attach');
        $data['type'] = PaymentType::Refund;
        $data['channel'] = $this->getAttribute('channel');
        $data['payment_id'] = $this->getKey();
        $data['order_no'] = $this->getAttribute('order_no');

        return self::defaultCreate($data, $merchant);
    }

    /**
     * 创建转账单.
     *
     * @param  string  $channel 支付通道.
     * @param  float  $amount 金额/元.
     * @param  string|null  $job 成功后需要执行的任务.
     * @param  array  $attach 附加信息.
     * @param  Model|Builder|null  $merchant 商户.
     */
    public static function transfer(string $channel, float $amount, string $job = null, array $attach = [], Model|Builder $merchant = null): Model|Builder
    {
        $data = compact('channel', 'amount', 'job', 'attach');
        $data['type'] = PaymentType::Transfer;

        return self::defaultCreate($data, $merchant);
    }

    /**
     * 发送请求，获取第三方接口响应数据.
     *
     * @throws ApiCustomError
     */
    public function send(array $data = []): mixed
    {
        $channel = self::getChannelClass($this->getAttribute('channel'));
        $method = strtolower($this->getAttribute('type')->name);

        return call_user_func([$channel, $method], $this, $data);
    }

    /**
     * 支付成功.
     *
     * @param  Carbon|string|null  $time 支付成功时间.
     */
    public function success(Carbon|string $time = null): void
    {
        $this->setAttribute('success_at', $time ?: now());
        $this->setAttribute('status', PaymentStatus::Success);
        $this->save();
        $job = $this->getAttribute('job');
        if ($job && class_exists($job) && method_exists($job, 'dispatch')) {
            call_user_func([$job, 'dispatch'], $this);
        }
    }

    /**
     * 支付失败.
     *
     * @param  string|null  $remark 失败原因.
     * @param  Carbon|string|null  $time 支付失败时间.
     */
    public function fail(string $remark = null, Carbon|string $time = null): void
    {
        $this->setAttribute('fail_at', $time ?: now());
        $this->setAttribute('status', PaymentStatus::Fail);
        $this->setAttribute('remark', $remark);
        $this->save();
    }
}
