<?php

namespace App\Providers;

use App\Support\WayfindingRequestIdentity;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::useBootstrapFive();

        RateLimiter::for('wayfinding-map', function (Request $request) {
            return [
                Limit::perMinute(120)
                    ->by(WayfindingRequestIdentity::clientKey($request)),
                Limit::perMinute(12_000)
                    ->by(WayfindingRequestIdentity::networkKey($request)),
            ];
        });

        RateLimiter::for('wayfinding-search', function (Request $request) {
            return [
                Limit::perMinute(30)
                    ->by(WayfindingRequestIdentity::clientKey($request)),
                Limit::perMinute(3_000)
                    ->by(WayfindingRequestIdentity::networkKey($request)),
            ];
        });

        RateLimiter::for('wayfinding-public-page', function (Request $request) {
            return [
                Limit::perMinute(60)
                    ->by(WayfindingRequestIdentity::clientKey($request)),
                Limit::perMinute(3_000)
                    ->by(WayfindingRequestIdentity::networkKey($request)),
            ];
        });
    }
}
