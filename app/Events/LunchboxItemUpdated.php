<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\LunchboxItemResource;
use App\Models\LunchboxItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LunchboxItemUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $action  The action performed (created, deleted)
     * @param  LunchboxItem|null  $lunchboxItem  The lunchbox item (null for deleted)
     * @param  int  $parentId  The parent ID to broadcast to
     * @param  int|null  $deletedItemId  The ID of the deleted item (only for delete action)
     */
    public function __construct(
        public string $action,
        public ?LunchboxItem $lunchboxItem,
        public int $parentId,
        public ?int $deletedItemId = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lunchbox.' . $this->parentId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'item' => $this->lunchboxItem ? new LunchboxItemResource($this->lunchboxItem->load('user')) : null,
            'deleted_item_id' => $this->deletedItemId,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'lunchbox.updated';
    }
}
