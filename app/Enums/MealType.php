<?php

declare(strict_types=1);

namespace App\Enums;

enum MealType: string
{
    case BREAKFAST = 'breakfast';
    case LUNCH = 'lunch';
    case ZVIERI = 'zvieri';
    case DINNER = 'dinner';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::BREAKFAST => 'Breakfast',
            self::LUNCH => 'Lunch',
            self::ZVIERI => 'Zvieri',
            self::DINNER => 'Dinner',
        };
    }

    /**
     * Get German label.
     */
    public function labelDe(): string
    {
        return match ($this) {
            self::BREAKFAST => 'Frühstück',
            self::LUNCH => 'Mittagessen',
            self::ZVIERI => 'Zvieri',
            self::DINNER => 'Abendessen',
        };
    }

    /**
     * Get sort order for display.
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::BREAKFAST => 1,
            self::LUNCH => 2,
            self::ZVIERI => 3,
            self::DINNER => 4,
        };
    }

    /**
     * Get all values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
