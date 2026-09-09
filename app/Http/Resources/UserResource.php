<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'active'      => (bool) $this->active,
            'business_id' => $this->business_id,
            'role'        => $this->whenLoaded('role', fn () => [
                'id'   => $this->role->id,
                'slug' => $this->role->slug,
                'name' => $this->role->name,
            ]),
            // El front usa esto para ocultar botones y precios de costo
            'permissions' => $this->role
                ? $this->role->permissions->pluck('slug')
                : ['*'],
            'business' => $this->whenLoaded('business', fn () => [
                'id'   => $this->business->id,
                'name' => $this->business->name,
                'ruc'  => $this->business->ruc,
            ]),
        ];
    }
}
