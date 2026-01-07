<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\MealType;
use Carbon\Carbon;

final readonly class CreateMealPlanData
{
    public function __construct(
        public int $userId,
        public Carbon $date,
        public MealType $mealType,
        public string $title,
    ) {}

    /**
     * Create from validated request data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            date: Carbon::parse($data['date']),
            mealType: MealType::from($data['meal_type']),
            title: $data['title'],
        );
    }

    /**
     * Convert to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'date' => $this->date,
            'meal_type' => $this->mealType,
            'title' => $this->title,
        ];
    }
}
