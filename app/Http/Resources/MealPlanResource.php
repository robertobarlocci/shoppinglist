<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealPlanResource extends JsonResource
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
            'user_id' => $this->user_id,
            'date' => $this->date->format('Y-m-d'),
            'meal_type' => $this->meal_type,
            'title' => $this->title,
            'ingredients' => MealPlanIngredientResource::collection($this->whenLoaded('ingredients')),
            'ingredients_count' => $this->whenLoaded('ingredients', function () {
                return $this->ingredients->count();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
