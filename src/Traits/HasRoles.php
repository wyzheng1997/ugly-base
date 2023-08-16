<?php

namespace Ugly\Base\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Ugly\Base\Models\Role;

/**
 * Trait HasRoles
 *
 * @mixin Model
 */
trait HasRoles
{
    /**
     * 缓存权限的时间 单位秒 默认0表示不缓存.
     */
    protected int $permissionCacheTTL = 0;

    /**
     * 拥有的角色.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'belongs', 'role_assignments',
            'belongs_id', 'role_id');
    }

    /**
     * 获取所有角色slug.
     */
    public function allRoles(): Collection
    {
        $cacheKey = static::class.'_roles_cache_'.$this->id;
        if ($this->permissionCacheTTL > 0) {
            return Cache::remember($cacheKey, $this->permissionCacheTTL, function () {
                return $this->roles->pluck('slug')->unique();
            });
        } else {
            Cache::forget($cacheKey);

            return $this->roles->pluck('slug')->unique();
        }
    }

    /**
     * 获取所有权限.
     */
    public function allPermissions($field = 'slug'): Collection
    {
        $cacheKey = static::class.'_permissions_cache_'.$this->id;
        if ($this->permissionCacheTTL > 0) {
            $permission = Cache::remember($cacheKey, $this->permissionCacheTTL, function () {
                return $this->roles->pluck('permissions')->flatten();
            });
        } else {
            Cache::forget($cacheKey);
            $permission = $this->roles->pluck('permissions')->flatten();

        }
        if ($field === '*') {
            return $permission;
        } else {
            return $permission->pluck($field)->unique();
        }
    }

    /**
     * 判断是否有权限.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->allPermissions()->contains($permission);
    }

    /**
     * 判断是否有任意权限.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->allPermissions()->intersect($permissions)->isNotEmpty();
    }

    /**
     * 判断是否有所有权限.
     */
    public function hasAllPermission(array $permissions): bool
    {
        return $this->allPermissions()->intersect($permissions)->count() === count($permissions);
    }

    /**
     * 判断是否有角色.
     */
    public function hasRole(string $role): bool
    {
        return $this->allRoles()->contains($role);
    }

    /**
     * 判断是否有任意角色.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->allRoles()->intersect($roles)->isNotEmpty();
    }

    /**
     * 判断是否有所有角色.
     */
    public function hasAllRole(array $roles): bool
    {
        return $this->allRoles()->intersect($roles)->count() === count($roles);
    }

    /**
     * 获取权限白名单. 格式为 ["GET:/api/v1/users/*", "POST:/api/v1/users"]
     */
    public function getPermissionWhiteList(): array
    {
        return [];
    }

    public function getPermissionRouteRules(): array
    {
        $cacheKey = static::class.'_permission_route_rules_'.$this->id;
        if ($this->permissionCacheTTL > 0) {
            return Cache::remember($cacheKey, $this->permissionCacheTTL, fn () => $this->formatPermissionRouteRules());
        } else {
            Cache::forget($cacheKey);

            return $this->formatPermissionRouteRules();
        }
    }

    /**
     * 格式化权限路由规则.
     */
    protected function formatPermissionRouteRules(): array
    {
        $allRules = [];
        foreach ($this->allPermissions('*')->filter(fn ($item) => $item->path && $item->method) as $item) {
            $paths = explode("\n", $item->path);
            foreach ($paths as $path) {
                $path = trim($path);
                if (empty($path)) {
                    continue;
                }
                $allRules[] = $item->method.':'.trim($path);
            }
        }

        return array_unique($allRules);
    }
}
