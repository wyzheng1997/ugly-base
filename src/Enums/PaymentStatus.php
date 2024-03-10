<?php

namespace Ugly\Base\Enums;

enum PaymentStatus: int
{
    // 状态: 1处理中 2成功 3失败
    const Processing = 1;

    const Success = 2;

    const Fail = 3;
}
