<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case PARENT = 'parent';
    case KID = 'kid';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PARENT => 'Parent',
            self::KID => 'Kid',
        };
    }

    /**
     * Get German label.
     */
    public function labelDe(): string
    {
        return match ($this) {
            self::PARENT => 'Elternteil',
            self::KID => 'Kind',
        };
    }

    /**
     * Check if the role has full access.
     */
    public function hasFullAccess(): bool
    {
        return $this === self::PARENT;
    }
}
