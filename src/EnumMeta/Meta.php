<?php

namespace Ugly\Base\EnumMeta;

/**
 * 枚举元数据.
 */
abstract class Meta
{
    final public function __construct(public mixed $value)
    {
        $this->value = $this->transform($value);
    }

    public static function make(mixed $value): static
    {
        return new static($value);
    }

    /**
     * 自定义转换.
     */
    protected function transform(mixed $value): mixed
    {
        return $value;
    }

    /**
     * 获取辕信息方法名.
     */
    final public static function method(): string
    {
        if (property_exists(static::class, 'alias')) {
            return static::${'alias'};
        }

        return str(static::class)->classBasename()->lcfirst();
    }
}
