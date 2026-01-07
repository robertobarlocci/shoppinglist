<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Item;
use App\Models\MealPlan;
use App\Models\User;

final readonly class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param array<string, mixed>|null $metadata
     */
    public function log(
        ActivityAction $action,
        ?User $user = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $subjectName = null,
        ?array $metadata = null,
    ): Activity {
        return Activity::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_name' => $subjectName,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log item added activity.
     */
    public function itemAdded(Item $item, User $user): Activity
    {
        return $this->log(
            action: ActivityAction::ITEM_ADDED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
            metadata: ['category' => $item->category?->name],
        );
    }

    /**
     * Log quick buy added activity.
     */
    public function quickBuyAdded(Item $item, User $user): Activity
    {
        return $this->log(
            action: ActivityAction::QUICK_BUY_ADDED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
        );
    }

    /**
     * Log item checked activity.
     */
    public function itemChecked(Item $item, User $user): Activity
    {
        return $this->log(
            action: ActivityAction::ITEM_CHECKED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
        );
    }

    /**
     * Log item deleted activity.
     */
    public function itemDeleted(Item $item, User $user): Activity
    {
        return $this->log(
            action: ActivityAction::ITEM_DELETED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
        );
    }

    /**
     * Log item restored activity.
     */
    public function itemRestored(Item $item, User $user): Activity
    {
        return $this->log(
            action: ActivityAction::ITEM_RESTORED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
        );
    }

    /**
     * Log item edited activity.
     *
     * @param array<string, mixed> $changes
     */
    public function itemEdited(Item $item, User $user, array $changes = []): Activity
    {
        return $this->log(
            action: ActivityAction::ITEM_EDITED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
            metadata: ['changes' => $changes],
        );
    }

    /**
     * Log recurring items triggered activity.
     *
     * @param array<string> $itemNames
     */
    public function recurringTriggered(array $itemNames): Activity
    {
        return $this->log(
            action: ActivityAction::RECURRING_TRIGGERED,
            user: null,
            subjectType: 'System',
            subjectId: null,
            subjectName: 'Recurring Items',
            metadata: ['items' => $itemNames, 'count' => count($itemNames)],
        );
    }

    /**
     * Log category created activity.
     */
    public function categoryCreated(Category $category, User $user): Activity
    {
        return $this->log(
            action: ActivityAction::CATEGORY_CREATED,
            user: $user,
            subjectType: 'Category',
            subjectId: $category->id,
            subjectName: $category->name,
        );
    }

    /**
     * Log user login activity.
     */
    public function userLogin(User $user): Activity
    {
        return $this->log(
            action: ActivityAction::USER_LOGIN,
            user: $user,
            subjectType: 'User',
            subjectId: $user->id,
            subjectName: $user->name,
        );
    }

    /**
     * Log meal plan created activity.
     */
    public function mealPlanCreated(MealPlan $mealPlan, User $user): Activity
    {
        return $this->log(
            action: ActivityAction::MEAL_PLAN_CREATED,
            user: $user,
            subjectType: 'MealPlan',
            subjectId: $mealPlan->id,
            subjectName: $mealPlan->title,
            metadata: [
                'date' => $mealPlan->date->format('Y-m-d'),
                'meal_type' => $mealPlan->meal_type->value,
            ],
        );
    }
}
