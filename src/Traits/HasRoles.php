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
     * 获取所有权限slug.
     */
    public function allPermissions(): Collection
    {
        $cacheKey = static::class.'_permissions_cache_'.$this->id;
        if ($this->permissionCacheTTL > 0) {
            return Cache::remember($cacheKey, $this->permissionCacheTTL, function () {
                return $this->roles->pluck('permissions')->flatten()->pluck('slug')->unique();
            });
        } else {
            Cache::forget($cacheKey);

            return $this->roles->pluck('permissions')->flatten()->pluck('slug')->unique();
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
}
