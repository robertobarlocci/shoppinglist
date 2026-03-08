<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar_color' => $request->user()->avatar_color,
                    'role' => $request->user()->role,
                    'parent_id' => $request->user()->parent_id,
                    'children' => $request->user()->isParent()
                        ? User::where('role', UserRole::KID)->get()->map(fn ($child) => [
                            'id' => $child->id,
                            'name' => $child->name,
                            'avatar_color' => $child->avatar_color,
                        ])->toArray()
                        : [],
                    'siblings' => $request->user()->isKid() && $request->user()->parent_id
                        ? User::where('parent_id', $request->user()->parent_id)
                            ->where('role', UserRole::KID)
                            ->get()->map(fn ($sibling) => [
                                'id' => $sibling->id,
                                'name' => $sibling->name,
                                'avatar_color' => $sibling->avatar_color,
                            ])->toArray()
                        : [],
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
