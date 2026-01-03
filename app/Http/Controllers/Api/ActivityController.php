<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Display a listing of activities.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 50);

        $activities = Activity::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return ActivityResource::collection($activities);
    }

    /**
     * Get recent unread activities.
     */
    public function unread(Request $request)
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
    public function markAsRead(Request $request)
    {
        // Placeholder implementation
        return response()->json(['message' => 'Aktivitäten als gelesen markiert']);
    }
}
