<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Item\MoveItemAction;
use App\Enums\ListType;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class OfflineSyncService
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private MoveItemAction $moveItemAction,
    ) {}

    /**
     * Process offline actions that were queued on the client.
     * Handles partial failures gracefully - successful actions commit even if others fail.
     *
     * @param  array  $actions  Array of offline actions
     * @param  User  $user  The user performing the sync
     * @return array Result of sync operation
     */
    public function processOfflineActions(array $actions, User $user): array
    {
        $results = [
            'success' => [],
            'conflicts' => [],
            'errors' => [],
            'synced_ids' => [], // Track which client-side action IDs were successfully synced
        ];

        foreach ($actions as $action) {
            // Process each action in its own transaction for partial failure handling
            DB::beginTransaction();

            try {
                $result = $this->processAction($action, $user);

                if ($result['status'] === 'success') {
                    $results['success'][] = $result;
                    // Track the client-side action ID for cleanup
                    if (isset($action['id'])) {
                        $results['synced_ids'][] = $action['id'];
                    }
                    DB::commit();
                } elseif ($result['status'] === 'conflict') {
                    $results['conflicts'][] = $result;
                    // Conflicts don't rollback - they're informational
                    DB::commit();
                } else {
                    $results['errors'][] = $result;
                    DB::rollBack();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Offline sync action failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'action' => $action,
                ]);

                $results['errors'][] = [
                    'status' => 'error',
                    'action' => $action['type'] ?? 'unknown',
                    'message' => $e->getMessage(),
                    'action_id' => $action['id'] ?? null,
                ];
            }
        }

        return $results;
    }

    /**
     * Process a single offline action.
     */
    private function processAction(array $action, User $user): array
    {
        try {
            $type = $action['type'] ?? null;
            $data = $action['data'] ?? [];
            $clientTimestamp = $action['timestamp'] ?? null;

            switch ($type) {
                case 'item:create':
                    return $this->handleCreateItem($data, $user, $clientTimestamp);

                case 'item:update':
                    return $this->handleUpdateItem($data, $user, $clientTimestamp);

                case 'item:delete':
                    return $this->handleDeleteItem($data, $user, $clientTimestamp);

                case 'item:move':
                    return $this->handleMoveItem($data, $user, $clientTimestamp);

                default:
                    return [
                        'status' => 'error',
                        'action' => $type,
                        'message' => 'Unknown action type',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'action' => $action['type'] ?? 'unknown',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle creating an item from offline action.
     */
    private function handleCreateItem(array $data, User $user, ?string $clientTimestamp): array
    {
        $item = Item::create([
            'name' => $data['name'],
            'quantity' => $data['quantity'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'list_type' => $data['list_type'],
            'created_by' => $user->id,
        ]);

        // Log activity
        if ($item->list_type === Item::LIST_TYPE_QUICK_BUY) {
            $this->activityLogger->quickBuyAdded($item, $user);
        } else {
            $this->activityLogger->itemAdded($item, $user);
        }

        return [
            'status' => 'success',
            'action' => 'item:create',
            'item_id' => $item->id,
        ];
    }

    /**
     * Handle updating an item from offline action.
     */
    private function handleUpdateItem(array $data, User $user, ?string $clientTimestamp): array
    {
        $item = Item::find($data['id']);

        if (! $item) {
            return [
                'status' => 'error',
                'action' => 'item:update',
                'message' => 'Item not found',
            ];
        }

        // Check for conflicts using last-write-wins strategy
        if ($clientTimestamp && $item->updated_at->timestamp > strtotime($clientTimestamp)) {
            return [
                'status' => 'conflict',
                'action' => 'item:update',
                'item_id' => $item->id,
                'server_version' => $item,
                'message' => 'Server version is newer',
            ];
        }

        $item->update([
            'name' => $data['name'] ?? $item->name,
            'quantity' => $data['quantity'] ?? $item->quantity,
            'category_id' => $data['category_id'] ?? $item->category_id,
        ]);

        $this->activityLogger->itemEdited($item, $user);

        return [
            'status' => 'success',
            'action' => 'item:update',
            'item_id' => $item->id,
        ];
    }

    /**
     * Handle deleting an item from offline action.
     */
    private function handleDeleteItem(array $data, User $user, ?string $clientTimestamp): array
    {
        $item = Item::find($data['id']);

        if (! $item) {
            // Item already deleted, consider it success
            return [
                'status' => 'success',
                'action' => 'item:delete',
                'item_id' => $data['id'],
                'message' => 'Item already deleted',
            ];
        }

        $item->moveToTrash();
        $this->activityLogger->itemDeleted($item, $user);

        return [
            'status' => 'success',
            'action' => 'item:delete',
            'item_id' => $item->id,
        ];
    }

    /**
     * Handle moving an item from offline action.
     */
    private function handleMoveItem(array $data, User $user, ?string $clientTimestamp): array
    {
        $item = Item::find($data['id']);

        if (! $item) {
            return [
                'status' => 'error',
                'action' => 'item:move',
                'message' => 'Item not found',
            ];
        }

        $fromList = $item->list_type;
        $toList = ListType::from($data['to_list']);

        // Use MoveItemAction for proper deduplication handling
        $result = $this->moveItemAction->execute($item, $toList, $user);

        return [
            'status' => 'success',
            'action' => 'item:move',
            'item_id' => $result['item'] !== null ? $result['item']->id : $item->id,
            'from' => $fromList,
            'to' => $data['to_list'],
            'deduplication' => $result['deduplication'],
            'message' => $result['message'],
        ];
    }
}
