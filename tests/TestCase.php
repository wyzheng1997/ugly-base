<?php

namespace Ugly\Base\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ugly\Base\UglyBaseServiceProvider;

class TestCase extends Orchestra
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate');
    }

    protected function getPackageProviders($app): array
    {
        return [
            UglyBaseServiceProvider::class,
        ];
    }
}