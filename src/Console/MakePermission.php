<?php

namespace Ugly\Base\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ugly\Base\Models\Permissions;

class MakePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ugly:make-permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成权限';

    protected $updated_at;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // 设置统一的时间，方便后续删除多余的权限
        $this->updated_at = now();

        $config = require database_path('permissions.php');
        $this->generateSuperPermission($config['belongs_type']);
        $this->generatePermission($config['permissions'], $config['belongs_type']);

        // 删除多余的权限
        $ids = Permissions::query()
            ->where('updated_at', '!=', $this->updated_at)
            ->pluck('id');
        DB::table('role_has_permissions')
            ->whereIn('permission_id', $ids)
            ->delete();
        Permissions::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * 生产超级权限.
     */
    protected function generateSuperPermission(array $belongs_type = []): void
    {
        foreach ($belongs_type as $item) {
            Permissions::query()
                ->updateOrCreate([
                    'slug' => 'super',
                    'belongs_type' => $item,
                ], [
                    'name' => '超级权限',
                    'pid' => 0,
                    'updated_at' => $this->updated_at,
                ]);
        }
    }

    /**
     * 生成其他权限.
     */
    protected function generatePermission(array $permissions = [], array $belongsType = [], int $pid = 0, string $type = ''): void
    {
        foreach ($permissions as $item) {
            $permission = Permissions::query()
                ->updateOrCreate([
                    'slug' => $item['slug'],
                    'belongs_type' => $type ?: $belongsType[$item['type']],
                ], [
                    'name' => $item['name'],
                    'pid' => $pid,
                    'updated_at' => $this->updated_at,
                ]);
            if (isset($item['children'])) {
                $this->generatePermission($item['children'], $belongsType, $permission->id, $permission->belongs_type);
            }
        }
    }
}
