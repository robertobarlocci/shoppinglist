<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealPlanSuggestionResource extends JsonResource
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
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar_color' => $this->user->avatar_color,
                ];
            }),
            'date' => $this->date->format('Y-m-d'),
            'meal_type' => $this->meal_type,
            'title' => $this->title,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approver' => $this->whenLoaded('approver', function () {
                return $this->approver ? [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                ] : null;
            }),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'meal_plan_id' => $this->meal_plan_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
