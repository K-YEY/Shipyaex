<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class PWAServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add PWA meta tags and scripts to Filament admin panel
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn (): string => Blade::render(<<<'HTML'
                <!-- PWA Meta Tags -->
                <meta name="theme-color" content="#0066cc">
                <meta name="apple-mobile-web-app-capable" content="yes">
                <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
                <meta name="apple-mobile-web-app-title" content="ShipManager">
                <meta name="mobile-web-app-capable" content="yes">
                
                <!-- PWA Manifest -->
                <link rel="manifest" href="/manifest.json">
                
                <!-- Apple Touch Icons -->
                <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
                <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
                <link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512x512.png">
                
                <!-- Favicon -->
                <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
                <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">
                
                <!-- Microsoft Tiles -->
                <meta name="msapplication-TileColor" content="#0066cc">
                <meta name="msapplication-TileImage" content="/icons/icon-512x512.png">
                <meta name="msapplication-config" content="/browserconfig.xml">
            HTML)
        );

        // Add PWA script before body end
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render(<<<'HTML'
                <!-- PWA Installation Script -->
                <script src="/js/pwa.js"></script>
                
                <script>
                    // Auto-request notification permission for logged-in users
                    if (window.pwaManager && 'Notification' in window) {
                        // Wait a bit before asking for permission
                        setTimeout(() => {
                            if (Notification.permission === 'default') {
                                window.pwaManager.requestNotificationPermission();
                            }
                        }, 5000);
                    }
                </script>
            HTML)
        );
    }
}
