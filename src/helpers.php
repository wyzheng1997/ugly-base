<?php

use Illuminate\Support\Collection;

if (! function_exists('arr2tree')) {
    /**
     * 数组/集合 转换成树级结构.
     */
    function arr2tree($list, Closure $transform = null,
        string $id = 'id', string $pid = 'pid', string $children = 'children'): array
    {
        [$map, $tree] = [[], []];
        foreach ($list as $item) {
            $map[data_get($item, $id)] = $transform ? call_user_func($transform, $item) : $item;
        }
        if ($list instanceof Collection) { // 集合转数组
            $list = $list->toArray();
        }

        foreach ($list as $item) {
            if (isset($item[$pid]) && isset($map[$item[$pid]])) {
                $map[$item[$pid]][$children][] = &$map[$item[$id]];
            } else {
                $tree[] = &$map[$item[$id]];
            }
        }
        unset($map);

        return $tree;
    }
}
