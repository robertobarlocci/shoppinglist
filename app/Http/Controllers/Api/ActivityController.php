<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ActivityController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of activities.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $defaultPerPage = config('shoppinglist.pagination.activities_per_page', 50);
        $maxPerPage = config('shoppinglist.pagination.max_per_page', 100);

        // Clamp per_page to configured limits
        $perPage = min(max((int) $request->query('per_page', $defaultPerPage), 1), $maxPerPage);

        $activities = Activity::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return ActivityResource::collection($activities);
    }

    /**
     * Get recent unread activities.
     */
    public function unread(): AnonymousResourceCollection
    {
        // For simplicity, return last 10 activities created in the last hour
        // In a full implementation, you'd track read status per user
        $activities = Activity::with('user')
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return ActivityResource::collection($activities);
    }

    /**
     * Mark activities as read.
     * Note: This is a placeholder. In a full implementation,
     * you'd have a pivot table to track read status per user.
     */
    public function markAsRead(): JsonResponse
    {
        // Placeholder implementation
        return $this->success(message: 'Aktivitäten als gelesen markiert');
    }
}
