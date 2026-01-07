<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MealPlanIngredientResource extends JsonResource
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
            'meal_plan_id' => $this->meal_plan_id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'item_id' => $this->item_id,
            'item' => $this->whenLoaded('item', function () {
                return $this->item ? [
                    'id' => $this->item->id,
                    'name' => $this->item->name,
                    'list_type' => $this->item->list_type,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
