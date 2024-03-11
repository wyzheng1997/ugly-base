<?php

namespace Ugly\Base\Tests;

use Ugly\Base\Enums\PaymentStatus;
use Ugly\Base\Models\Payment;
use Ugly\Base\Support\PaymentChannel;

class PaymentTest extends TestCase
{
    private $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payment = Payment::pay(PaymentChannel::class, 12.5, '', now()->addMinutes(30));
    }

    public function test_create_pay()
    {
        $res = $this->payment->send();
        $this->assertTrue($res === 'pay');
    }

    public function test_create_refund()
    {
        $this->payment->status = PaymentStatus::Success;
        $this->payment->save();
        $res = $this->payment->refund(12.5)->send();
        $this->assertTrue($res === 'refund');
    }
}
