<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Process offline actions that were queued on the client.
     *
     * @param array $actions Array of offline actions
     * @param User $user The user performing the sync
     * @return array Result of sync operation
     */
    public function processOfflineActions(array $actions, User $user): array
    {
        $results = [
            'success' => [],
            'conflicts' => [],
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($actions as $action) {
                $result = $this->processAction($action, $user);

                if ($result['status'] === 'success') {
                    $results['success'][] = $result;
                } elseif ($result['status'] === 'conflict') {
                    $results['conflicts'][] = $result;
                } else {
                    $results['errors'][] = $result;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Offline sync failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'actions' => $actions,
            ]);

            throw $e;
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

        if (!$item) {
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

        if (!$item) {
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

        if (!$item) {
            return [
                'status' => 'error',
                'action' => 'item:move',
                'message' => 'Item not found',
            ];
        }

        $fromList = $item->list_type;
        $toList = $data['to_list'];

        $item->moveTo($toList);

        // Log appropriate activity
        if ($toList === Item::LIST_TYPE_INVENTORY) {
            $this->activityLogger->itemChecked($item, $user);
        }

        return [
            'status' => 'success',
            'action' => 'item:move',
            'item_id' => $item->id,
            'from' => $fromList,
            'to' => $toList,
        ];
    }
}
