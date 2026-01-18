<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('lunchbox.{parentId}', function ($user, $parentId) {
    // Only parents can subscribe to their own lunchbox channel
    return $user->isParent() && (int) $user->id === (int) $parentId;
});
