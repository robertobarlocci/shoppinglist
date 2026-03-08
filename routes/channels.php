<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('lunchbox.{parentId}', function ($user, $parentId) {
    // Parents can subscribe to their own lunchbox channel
    if ($user->isParent() && (int) $user->id === (int) $parentId) {
        return true;
    }

    // Kids can subscribe to their parent's lunchbox channel
    return $user->isKid() && $user->parent_id !== null && (int) $user->parent_id === (int) $parentId;
});
