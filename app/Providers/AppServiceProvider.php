<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Item;
use App\Policies\ItemPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production (behind Cloudflare/reverse proxy)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register policies
        Gate::policy(Item::class, ItemPolicy::class);
    }
}
