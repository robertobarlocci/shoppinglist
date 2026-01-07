<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LunchboxItem;
use App\Models\User;

final class LunchboxItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LunchboxItem $lunchboxItem): bool
    {
        // Kids can view their own items
        if ($user->isKid() && $lunchboxItem->user_id === $user->id) {
            return true;
        }

        // Parents can view their children's items
        if ($user->isParent()) {
            $childIds = $user->children()->pluck('id')->toArray();

            return in_array($lunchboxItem->user_id, $childIds);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only kids can create lunchbox items
        return $user->isKid();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LunchboxItem $lunchboxItem): bool
    {
        // Only the owner kid can update their lunchbox item
        return $user->isKid() && $lunchboxItem->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LunchboxItem $lunchboxItem): bool
    {
        // Only the owner kid can delete their lunchbox item
        return $user->isKid() && $lunchboxItem->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LunchboxItem $lunchboxItem): bool
    {
        return $user->isKid() && $lunchboxItem->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LunchboxItem $lunchboxItem): bool
    {
        return $user->isKid() && $lunchboxItem->user_id === $user->id;
    }
}
