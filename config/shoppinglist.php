<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Shopping List Settings
    |--------------------------------------------------------------------------
    |
    | These are the main settings for the shopping list application.
    |
    */

    'pagination' => [
        // Default items per page for lists
        'default_per_page' => 15,
        // Maximum items per page (security limit)
        'max_per_page' => 100,
        // Activities per page
        'activities_per_page' => 50,
    ],

    'suggestions' => [
        // Minimum characters to trigger suggestions
        'min_query_length' => 2,
        // Maximum suggestions to return
        'max_results' => 10,
    ],

    'sync' => [
        // Batch size for offline sync operations
        'batch_size' => 50,
        // Maximum age of synced data in hours
        'max_data_age_hours' => 24,
    ],

    'trash' => [
        // Days before items are permanently deleted
        'retention_days' => 30,
    ],

    'recurring' => [
        // Days ahead to check for recurring items
        'check_days_ahead' => 7,
    ],

    'cache' => [
        // TTL for suggestion cache in minutes
        'suggestions_ttl' => 60,
        // TTL for category cache in minutes
        'categories_ttl' => 120,
    ],
];
