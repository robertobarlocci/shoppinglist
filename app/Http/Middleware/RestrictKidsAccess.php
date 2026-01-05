<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictKidsAccess
{
    /**
     * Handle an incoming request.
     *
     * Kids are only allowed to access /meal-planner and related API endpoints.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only apply restrictions to kid accounts
        if ($user && $user->isKid()) {
            $path = $request->path();

            // Allowed paths for kids
            $allowedPaths = [
                'meal-planner',
                'api/user',
                'api/meal-plans',
                'api/meal-suggestions',
                'logout',
            ];

            // Check if current path is allowed
            $isAllowed = false;
            foreach ($allowedPaths as $allowedPath) {
                if (str_starts_with($path, $allowedPath)) {
                    $isAllowed = true;
                    break;
                }
            }

            // If not allowed, redirect kids to meal planner
            if (!$isAllowed) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Access forbidden for kids accounts'], 403);
                }
                return redirect('/meal-planner');
            }
        }

        return $next($request);
    }
}
