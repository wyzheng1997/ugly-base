<?php

namespace Ugly\Base\Enums;

enum PaymentType: int
{
    // 支付类型: 1付款 2退款 3转账
    const Pay = 1;

    const Refund = 2;

    const Transfer = 3;
}
