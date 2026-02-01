<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Widgets\OrdersReportWidget;
use App\Filament\Widgets\OrdersStatsOverview;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Moataz01\FilamentNotificationSound\FilamentNotificationSoundPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use WatheqAlshowaiter\FilamentStickyTableHeader\StickyTableHeaderPlugin;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('logo.png'))
            ->login(Login::class)
            ->colors([
                'primary' => Color::Red,
            ])->sidebarCollapsibleOnDesktop(true)
            ->renderHook(
                'panels::head.end',
                fn (): string => '
                    <link rel="manifest" href="/manifest.json">
                    <meta name="theme-color" content="#dc2626">
                    <link rel="icon" type="image/png" href="/logo.png">
                    <link rel="apple-touch-icon" href="/logo.png">
                    <meta name="apple-mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-status-bar-style" content="default">
                    <meta name="apple-mobile-web-app-title" content="ShipManager">
                ',
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => '<script src="/js/pwa.js"></script>',
            )
            ->renderHook(
                'panels::user-menu.before',
                fn (): \Illuminate\Contracts\View\View => view('filament.components.pwa-install'),
            )
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                OrdersStatsOverview::class,
                OrdersReportWidget::class,
                \App\Filament\Widgets\OrdersByGovernorateChart::class,
            ])

            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                
                // Apex Charts Plugin
                \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make(),

                // Notification Sound Plugin
                FilamentNotificationSoundPlugin::make()
                    ->soundPath(asset('sound.mp3')) // Custom sound path
                    ->volume(1.0) // Volume (0.0 to 1.0)
                    ->showAnimation(true) // Show animation on notification badge
                    ->enabled(true), // Enable/disable the plugin
            StickyTableHeaderPlugin::make()->shouldScrollToTopOnPageChanged(enabled:  true, behavior: "smooth"),        
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,

            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');


    }
}
