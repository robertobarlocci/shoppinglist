<?php

declare(strict_types=1);

namespace App\Enums;

enum ListType: string
{
    case QUICK_BUY = 'quick_buy';
    case TO_BUY = 'to_buy';
    case INVENTORY = 'inventory';
    case TRASH = 'trash';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::QUICK_BUY => 'Quick Buy',
            self::TO_BUY => 'To Buy',
            self::INVENTORY => 'Inventory',
            self::TRASH => 'Trash',
        };
    }

    /**
     * Get German label.
     */
    public function labelDe(): string
    {
        return match ($this) {
            self::QUICK_BUY => 'Schnellkauf',
            self::TO_BUY => 'Einkaufsliste',
            self::INVENTORY => 'Inventar',
            self::TRASH => 'Papierkorb',
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

    /**
     * Get all active list types (excluding trash).
     *
     * @return array<self>
     */
    public static function active(): array
    {
        return [
            self::QUICK_BUY,
            self::TO_BUY,
            self::INVENTORY,
        ];
    }
}
