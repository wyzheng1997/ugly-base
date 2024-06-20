<?php

namespace Ugly\Base\Exceptions;

use Illuminate\Http\JsonResponse;
use Ugly\Base\Traits\ApiResponse;

class ApiCustomError extends \Exception
{
    use ApiResponse;

    protected ?int $httpCode;

    public function __construct(string $msg = '操作失败', int $code = 400, ?int $httpCode = null)
    {
        parent::__construct();
        $this->message = $msg;
        $this->code = $code;
        $this->httpCode = $httpCode;
    }

    public function render(): JsonResponse
    {
        return $this->failed($this->message, $this->code, $this->httpCode);
    }
}
