<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'action' => $this->action,
            'icon' => $this->icon,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_name' => $this->subject_name,
            'metadata' => $this->metadata,
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar_color' => $this->user->avatar_color,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
