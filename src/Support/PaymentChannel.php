<?php

namespace Ugly\Base\Support;

use Illuminate\Http\Request;

class PaymentChannel
{
    public static function pay($payment, array $data = [])
    {
        return 'pay';
    }

    public static function refund($payment, array $data = [])
    {
        return 'refund';
    }

    public static function transfer($payment, array $data = [])
    {
        return 'transfer';
    }

    public static function notify(Request $request)
    {
        return [];
    }
}
