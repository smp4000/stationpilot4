<?php
namespace App\Providers\Filament;
use App\Http\Middleware\SetAdminPermissionsTeam;
use Filament\Http\Middleware\Authenticate;
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
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Stationpilot')
            ->brandLogo(null)
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
                NavigationGroup::make('Mandanten')
                    ->icon('heroicon-o-building-office-2'),
                NavigationGroup::make('Benutzer')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make('DSGVO & Audit')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(),
                NavigationGroup::make('System')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\\Filament\\Admin\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Admin/Pages'),
                for: 'App\\Filament\\Admin\\Pages'
            )
            ->pages([Dashboard::class])
            ->discoverWidgets(
                in: app_path('Filament/Admin/Widgets'),
                for: 'App\\Filament\\Admin\\Widgets'
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
            ->authMiddleware([
                Authenticate::class,
                SetAdminPermissionsTeam::class,
            ]);
    }
}
