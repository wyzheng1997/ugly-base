<?php

namespace Ugly\Base\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 角色列表资源
 */
class RoleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'permissions' => PermissionListResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
