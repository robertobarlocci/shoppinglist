<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

final class ItemPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Note: Currently all authenticated users share items (single household assumption).
     * For multi-household support, add household_id scoping here.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Item $item): bool
    {
        // All authenticated users can view items (single household)
        // For multi-household: return $user->household_id === $item->household_id;
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Parents can create items, kids have restricted access
        return $user->isParent();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Item $item): bool
    {
        // Parents can update any item
        // For multi-household: add && $user->household_id === $item->household_id
        return $user->isParent();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Item $item): bool
    {
        // Parents can delete any item
        return $user->isParent();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Item $item): bool
    {
        return $user->isParent();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Item $item): bool
    {
        return $user->isParent();
    }

    /**
     * Determine whether the user can move items between lists.
     */
    public function move(User $user, Item $item): bool
    {
        // Parents can move items freely
        // Kids might have restricted movement (e.g., only to inventory when "checking")
        return $user->isParent();
    }
}
