<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityAction: string
{
    case ITEM_ADDED = 'item_added';
    case QUICK_BUY_ADDED = 'quick_buy_added';
    case ITEM_CHECKED = 'item_checked';
    case ITEM_DELETED = 'item_deleted';
    case ITEM_RESTORED = 'item_restored';
    case ITEM_EDITED = 'item_edited';
    case RECURRING_TRIGGERED = 'recurring_triggered';
    case CATEGORY_CREATED = 'category_created';
    case USER_LOGIN = 'user_login';
    case MEAL_PLAN_CREATED = 'meal_plan_created';

    /**
     * Get the icon for this action.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ITEM_ADDED => '🛒',
            self::QUICK_BUY_ADDED => '🔥',
            self::ITEM_CHECKED => '✅',
            self::ITEM_DELETED => '🗑️',
            self::ITEM_RESTORED => '♻️',
            self::ITEM_EDITED => '✏️',
            self::RECURRING_TRIGGERED => '🔄',
            self::CATEGORY_CREATED => '🏷️',
            self::USER_LOGIN => '👤',
            self::MEAL_PLAN_CREATED => '📅',
        };
    }

    /**
     * Get the German description template.
     */
    public function descriptionTemplate(): string
    {
        return match ($this) {
            self::ITEM_ADDED => '{user} hat "{subject}" zur Einkaufsliste hinzugefügt',
            self::QUICK_BUY_ADDED => '{user} hat "{subject}" als Quick Buy hinzugefügt',
            self::ITEM_CHECKED => '{user} hat "{subject}" abgehakt',
            self::ITEM_DELETED => '{user} hat "{subject}" gelöscht',
            self::ITEM_RESTORED => '{user} hat "{subject}" wiederhergestellt',
            self::ITEM_EDITED => '{user} hat "{subject}" bearbeitet',
            self::RECURRING_TRIGGERED => 'Wiederkehrende Artikel automatisch hinzugefügt',
            self::CATEGORY_CREATED => '{user} hat die Kategorie "{subject}" erstellt',
            self::USER_LOGIN => '{user} hat sich eingeloggt',
            self::MEAL_PLAN_CREATED => '{user} hat "{subject}" zum Essensplan hinzugefügt',
        };
    }

    /**
     * Build the description with user and subject.
     */
    public function description(string $userName, ?string $subjectName = null): string
    {
        $template = $this->descriptionTemplate();

        return str_replace(
            ['{user}', '{subject}'],
            [$userName, $subjectName ?? ''],
            $template,
        );
    }
}
