<?php

namespace App\Providers;

use App\Models\CollectedClient;
use App\Models\CollectedShipper;
use App\Policies\CollectedClientPolicy;
use App\Policies\CollectedShipperPolicy;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Gate;
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
                ->locales(['ar', 'en'])
                ->flags([
                    'ar' => asset('flags/ar.png'),
                    'en' => asset('flags/en.png'),
                ])
                ->labels([
                    'ar' => 'عربي',
                    'en' => 'English',
                ])
                ->visible(insidePanels: true, outsidePanels: false);
        });

        // Register Policies
        Gate::policy(CollectedShipper::class, CollectedShipperPolicy::class);
        Gate::policy(CollectedClient::class, CollectedClientPolicy::class);

        // Register Observers
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\CollectedClient::observe(\App\Observers\CollectedClientObserver::class);
        \App\Models\CollectedShipper::observe(\App\Observers\CollectedShipperObserver::class);
    }
}
