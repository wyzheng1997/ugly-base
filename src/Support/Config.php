<?php

namespace Ugly\Base\Support;

use Illuminate\Support\Fluent;
use Ugly\Base\Models\SysConfig;

class Config extends Fluent
{
    public function get($key, $default = null)
    {
        if (empty($this->attributes)) {
            $this->attributes = SysConfig::query()
                ->get(['value', 'slug'])
                ->pluck('value', 'slug')
                ->toArray();
        }

        return parent::get($key, $default);
    }

    public function set($key, $value = null): static
    {
        $data = is_array($key) ? $key : [$key => $value];
        foreach ($data as $slug => $val) {
            SysConfig::query()->updateOrCreate(['slug' => $slug], ['value' => $val]);
            parent::offsetSet($slug, $val);
        }

        return $this;
    }
}
