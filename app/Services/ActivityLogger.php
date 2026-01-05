<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;

class ActivityLogger
{
    /**
     * Log an activity.
     */
    public function log(
        string $action,
        ?User $user = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $subjectName = null,
        ?array $metadata = null
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
    public function itemAdded($item, User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_ITEM_ADDED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
            metadata: ['category' => $item->category?->name]
        );
    }

    /**
     * Log quick buy added activity.
     */
    public function quickBuyAdded($item, User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_QUICK_BUY_ADDED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name
        );
    }

    /**
     * Log item checked activity.
     */
    public function itemChecked($item, User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_ITEM_CHECKED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name
        );
    }

    /**
     * Log item deleted activity.
     */
    public function itemDeleted($item, User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_ITEM_DELETED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name
        );
    }

    /**
     * Log item restored activity.
     */
    public function itemRestored($item, User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_ITEM_RESTORED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name
        );
    }

    /**
     * Log item edited activity.
     */
    public function itemEdited($item, User $user, array $changes = []): Activity
    {
        return $this->log(
            action: Activity::ACTION_ITEM_EDITED,
            user: $user,
            subjectType: 'Item',
            subjectId: $item->id,
            subjectName: $item->name,
            metadata: ['changes' => $changes]
        );
    }

    /**
     * Log recurring items triggered activity.
     */
    public function recurringTriggered(array $itemNames): Activity
    {
        return $this->log(
            action: Activity::ACTION_RECURRING_TRIGGERED,
            user: null,
            subjectType: 'System',
            subjectId: null,
            subjectName: 'Recurring Items',
            metadata: ['items' => $itemNames, 'count' => count($itemNames)]
        );
    }

    /**
     * Log category created activity.
     */
    public function categoryCreated($category, User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_CATEGORY_CREATED,
            user: $user,
            subjectType: 'Category',
            subjectId: $category->id,
            subjectName: $category->name
        );
    }

    /**
     * Log user login activity.
     */
    public function userLogin(User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_USER_LOGIN,
            user: $user,
            subjectType: 'User',
            subjectId: $user->id,
            subjectName: $user->name
        );
    }

    /**
     * Log meal plan created activity.
     */
    public function mealPlanCreated($mealPlan, User $user): Activity
    {
        return $this->log(
            action: Activity::ACTION_MEAL_PLAN_CREATED,
            user: $user,
            subjectType: 'MealPlan',
            subjectId: $mealPlan->id,
            subjectName: $mealPlan->title,
            metadata: [
                'date' => $mealPlan->date->format('Y-m-d'),
                'meal_type' => $mealPlan->meal_type,
            ]
        );
    }
}
