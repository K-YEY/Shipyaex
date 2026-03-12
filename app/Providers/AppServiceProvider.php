<?php

namespace App\Providers;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use App\Models\CollectedClient;
use App\Models\CollectedShipper;
use App\Policies\CollectedClientPolicy;
use App\Policies\CollectedShipperPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
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
        // Configure Language Switch
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar','en'])
                  ->flags([
                'ar' => asset('flags/ar.png'),
                'en' => asset('flags/en.png'),
            ])
            ->flagsOnly();
        });

        // Register Policies
        Gate::policy(CollectedShipper::class, CollectedShipperPolicy::class);
        Gate::policy(CollectedClient::class, CollectedClientPolicy::class);
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\Setting::class, \App\Policies\SettingPolicy::class);
        Gate::policy(\App\Models\ShippingContent::class, \App\Policies\ShippingContentPolicy::class);

        // Register Observers
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\CollectedClient::observe(\App\Observers\CollectedClientObserver::class);
        \App\Models\CollectedShipper::observe(\App\Observers\CollectedShipperObserver::class);

        // ⚡ PERFORMANCE MONITORING (local env only)
            // 1️⃣ Log any query slower than 500ms
            $slowQueryThresholdMs = 500;
            DB::listen(function ($query) use ($slowQueryThresholdMs) {
                if ($query->time > $slowQueryThresholdMs) {
                    Log::channel('daily')->warning('[SLOW QUERY] {time}ms | {sql}', [
                        'time' => round($query->time),
                        'sql'  => $query->sql,
                        'bindings' => $query->bindings,
                    ]);
                }
            });

            // 2️⃣ Prevent lazy loading (N+1 detection) — throws exception if accessed without eager loading
            // Comment this out if it's too strict for your workflow:
            Model::preventLazyLoading();
        
    }
}
