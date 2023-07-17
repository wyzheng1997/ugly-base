<?php

namespace Ugly\Base\Traits;

use BackedEnum;
use Illuminate\Support\Collection;
use ReflectionEnumUnitCase;
use Ugly\Base\EnumMeta\Meta;
use UnitEnum;

/**
 * Trait EnumTraits.
 *
 * @mixin UnitEnum
 */
trait EnumTraits
{
    /**
     * 获取所有枚举name.
     */
    public static function names(): array
    {
        return array_column(static::cases(), 'name');
    }

    /**
     * 获取所有枚举value.
     */
    public static function values(): array
    {
        if (! is_subclass_of(static::class, BackedEnum::class)) {
            return static::names();
        }

        return array_column(static::cases(), 'value');
    }

    /**
     * 将枚举转换为键值对.
     */
    public static function options(string $meta = 'description'): array
    {
        $keys = collect(static::cases())
            ->map(fn (UnitEnum $case) => /** @var static $case */ $case());

        $values = collect(static::cases())
            ->map(fn (UnitEnum $case) => /** @var static $case */ $case->label($meta));

        return $keys->combine($values)->all();
    }

    /**
     * 将枚举信息转换成二维数组.
     */
    public static function tables(): array
    {
        $tables = collect(static::cases())
            ->map(fn (UnitEnum $case) => /** @var static $case */ $case->map());

        $allKeys = $tables->collapse()->map(fn () => null);

        return $tables
            ->map(fn ($map) => $allKeys->merge($map)->all())
            ->all();
    }

    /**
     * 通过枚举名称获取枚举实例.
     *
     * @return $this|null
     */
    public function tryFromName(string $name): ?static
    {
        return collect(static::cases())->first(fn (UnitEnum $case) => $case->name === $name) ?: null;
    }

    /**
     * 获取枚举实例的label.
     */
    public function label(string $method = 'description'): string
    {
        if (method_exists($this, 'getLabel')) {
            return $this->getLabel();
        }
        $meta = collect($this->metas())
            ->first(fn (Meta $attr) => $attr::method() === $method);
        if ($meta) {
            return $meta->value;
        } else {
            return str($this->name)->lower()->studly();
        }
    }

    public function map(): array
    {
        return collect($this->metas())
            ->flatMap(fn (Meta $meta) => [$meta::method() => $meta->value])
            ->merge(['name' => $this->name])
            ->when($this instanceof BackedEnum,
                fn (Collection $collection) => $collection
                    ->merge(['value' => $this->value])
            )
            ->all();
    }

    /**
     * 获取枚举元数据实例.
     *
     * @return Meta[]
     */
    public function metas(): array
    {
        $metas = [];

        $rfe = new ReflectionEnumUnitCase($this, $this->name);
        foreach ($rfe->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof Meta) {
                $metas[] = $instance;
            }
        }

        return $metas;
    }

    public function __invoke(): int|string
    {
        return $this instanceof BackedEnum ? $this->value : $this->name;
    }

    public function __call(string $property, $arguments): mixed
    {
        return collect($this->metas())
            ->first(fn (Meta $attr) => $attr::method() === $property)
            ?->value;
    }
}
