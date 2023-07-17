<?php

namespace Ugly\Base\Enums;

use Ugly\Base\EnumMeta\Description;
use Ugly\Base\Traits\EnumTraits;

enum ApiResponseCode: int
{
    use EnumTraits;
    #[Description('轻提示')]
    case Toast = 400;

    #[Description('未登录')]
    case Unauthorized = 401;

    #[Description('无权限')]
    case Forbidden = 403;
}
