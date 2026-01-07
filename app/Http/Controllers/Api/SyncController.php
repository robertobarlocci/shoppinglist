<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OfflineSyncService;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(
        private OfflineSyncService $syncService
    ) {}

    /**
     * Process offline sync actions.
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'actions' => 'required|array',
            'actions.*.id' => 'nullable|integer', // Client-side action ID for tracking
            'actions.*.type' => 'required|string',
            'actions.*.data' => 'required|array',
            'actions.*.timestamp' => 'nullable|string',
        ]);

        $results = $this->syncService->processOfflineActions(
            $validated['actions'],
            auth()->user()
        );

        return response()->json([
            'message' => 'Sync completed',
            'results' => $results,
            'synced_count' => count($results['success']),
            'conflict_count' => count($results['conflicts']),
            'error_count' => count($results['errors']),
            'synced_ids' => $results['synced_ids'] ?? [],
            'conflicts' => $results['conflicts'] ?? [],
        ]);
    }
}
