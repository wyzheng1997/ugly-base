<?php

namespace Ugly\Base;

use Illuminate\Support\ServiceProvider;
use Ugly\Base\Console\InstallCommand;
use Ugly\Base\Support\Config;

class UglyBaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册系统配置服务.
        $this->app->singleton('ugly.config', Config::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config' => config_path()], 'ugly-base-config');
        }
    }
}
