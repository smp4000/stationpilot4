<?php
namespace App\Providers\Filament;
use App\Http\Middleware\CheckTenantStatus;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\SetPartnerPermissionsTeam;
use Filament\Http\Middleware\Authenticate;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->brandName('Stationpilot')
            ->colors([
                'primary' => Color::hex('#003B95'),
                'gray'    => Color::Slate,
                'danger'  => Color::Rose,
                'info'    => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->navigationGroups([
                NavigationGroup::make('Tankstellen')
                    ->icon('heroicon-o-map-pin'),
                NavigationGroup::make('Personal')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make('Finanzen')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Einstellungen')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(
                in: app_path('Filament/App/Resources'),
                for: 'App\\Filament\\App\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/App/Pages'),
                for: 'App\\Filament\\App\\Pages'
            )
            ->pages([Dashboard::class])
            ->discoverWidgets(
                in: app_path('Filament/App/Widgets'),
                for: 'App\\Filament\\App\\Widgets'
            )
            ->widgets([AccountWidget::class])
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
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">' .
                    '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>'
                )
            )
            ->authMiddleware([
                Authenticate::class,
                SetPartnerPermissionsTeam::class,  // P03: setzt Team-ID + tenant_id in Session
                EnsureTenantContext::class,         // P04: verifiziert Session-Konsistenz
                CheckTenantStatus::class,           // P04: prüft Abo-Status
            ]);
    }
}
