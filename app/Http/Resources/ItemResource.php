<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
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
            'name' => $this->name,
            'quantity' => $this->quantity,
            'list_type' => $this->list_type,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'color' => $this->category->color,
                    'icon' => $this->category->icon,
                ];
            }),
            'recurring_schedule' => $this->whenLoaded('recurringSchedule', function () {
                return $this->recurringSchedule ? [
                    'id' => $this->recurringSchedule->id,
                    'monday' => $this->recurringSchedule->monday,
                    'tuesday' => $this->recurringSchedule->tuesday,
                    'wednesday' => $this->recurringSchedule->wednesday,
                    'thursday' => $this->recurringSchedule->thursday,
                    'friday' => $this->recurringSchedule->friday,
                    'saturday' => $this->recurringSchedule->saturday,
                    'sunday' => $this->recurringSchedule->sunday,
                    'description' => $this->recurringSchedule->getScheduleDescription(),
                    'last_triggered_at' => $this->recurringSchedule->last_triggered_at,
                ] : null;
            }),
            'is_recurring' => $this->isRecurring(),
            'recurring_source_id' => $this->recurring_source_id,
            'created_by' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'avatar_color' => $this->creator->avatar_color,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'moved_at' => $this->moved_at,
        ];
    }
}
