<?php

namespace Ugly\Base;

use Illuminate\Support\Fluent;
use Illuminate\Support\ServiceProvider;

class UglyBaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('ugly.base.context', Fluent::class);
    }
}
