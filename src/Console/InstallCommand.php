<?php

namespace Ugly\Base\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'ugly:install';

    protected $description = 'Install Ugly';

    public function handle(): void
    {
        if (config('ugly.config.enable')) {
            $this->installConfig();
        }
        if (config('ugly.payment.enable')) {
            $this->installPayment();
        }

        // 执行迁移
        $this->call('migrate');
    }

    private function installConfig(): void
    {
        $this->generateMigration('sys_configs_table');
    }

    private function installPayment(): void
    {
        $this->generateMigration('payments_table');
    }

    /**
     * 生成迁移文件.
     */
    private function generateMigration(string $name): void
    {
        $exists = glob(database_path('migrations/*_create_'.$name.'.php'));
        if (count($exists)) {
            $this->info('跳过已存在的迁移文件：'.$exists[0]);

            return;
        }
        $path = database_path('migrations/'.date('Y_m_d_His').'_create_'.$name.'.php');
        file_put_contents($path, file_get_contents(__DIR__.'/../../database/migrations/'.$name.'.php'));
        $this->info('生成迁移文件：'.$path);
    }
}
