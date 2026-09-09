<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'icon'           => $this->icon,
            'sort_order'     => (int) $this->sort_order,
            'active'         => (bool) $this->active,
            'products_count' => $this->whenCounted('products'),
        ];
    }
}
