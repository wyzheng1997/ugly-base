<?php

namespace Ugly\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Ugly\Base\Traits\ApiResource;

/**
 * 字典基类.
 */
class DictBaseController extends Controller
{
    use ApiResource;

    /**
     * 枚举的命名空间，转换时自动剔除.
     */
    protected string $namespace = 'App\Enums';

    /**
     * 需要转换的枚举.
     *
     * @var array|string[]
     */
    protected array $enums = [];

    /**
     * 字典列表.
     */
    public function index(): JsonResponse
    {
        $dict = [];
        foreach ($this->enums as $enum) {
            $dict[$this->class2name($enum)] = $enum::tables();
        }

        return $this->success($dict);
    }

    /**
     * 类名转换为字典名.
     */
    protected function class2name(string $class): string
    {
        // 去除命名空间(正则匹配开头)
        $name = preg_replace('/^'.preg_quote($this->namespace).'\\\/', '', $class);
        // 去除'\'
        $name = str_replace('\\', '', $name);
        // 将驼峰转换为下划线
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $name));
    }
}
