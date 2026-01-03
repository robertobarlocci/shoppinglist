<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_default' => $this->is_default,
            'sort_order' => $this->sort_order,
            'items_count' => $this->when($this->relationLoaded('items'), function () {
                return $this->items->count();
            }),
        ];
    }
}
