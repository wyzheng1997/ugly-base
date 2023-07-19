<?php

namespace Ugly\Base;

use Illuminate\Support\ServiceProvider;

class UglyBaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            Console\MakePermission::class,
        ]);
    }

    public function boot(): void
    {
        $this->registerPublishing();
    }

    /**
     * 注册资源发布.
     */
    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'ugly-base-migrations');

            $this->publishes([
                __DIR__.'/../database/permissions.php' => database_path('permissions.php'),
            ], 'ugly-base-permissions');
        }
    }
}
