<?php

namespace Ugly\Base\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 权限列表资源。
 */
class PermissionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
        ];
    }
}
