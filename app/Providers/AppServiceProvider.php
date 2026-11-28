<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Model::preventLazyLoading(! $this->app->isProduction());

        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));

        Vite::useScriptTagAttributes(['data-turbo-track' => 'reload']);
        Vite::useStyleTagAttributes(['data-turbo-track' => 'reload']);

        // Paksa HTTPS hanya saat diakses lewat tunnel (Cloudflare/ngrok)
        if (! $this->app->runningInConsole() && request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        // Eager-load roles & gudang once for layout partials to prevent duplicate/lazy queries.
        View::composer(['components.sidebar', 'components.header', 'components.active-gudang-selector'], function (): void {
            $user = Auth::user();
            if ($user !== null) {
                $user->loadMissing('roles');
                if (! $user->hasAnyRole(['Super Admin', 'super_admin'])) {
                    $user->loadMissing([
                        'gudang' => static fn ($query) => $query->orderBy('gudangs.nama'),
                    ]);
                }
            }
        });
    }
}
