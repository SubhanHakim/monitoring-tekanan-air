<?php
// filepath: app/Providers/Filament/UnitPanelProvider.php

namespace App\Providers\Filament;

use App\Filament\Unit\Pages\Dashboard;
use App\Filament\Unit\Widgets\UnitReportStatsWidget;
use App\Http\Middleware\UnitMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\LegacyComponents\Widget;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UnitPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('unit')
            ->path('unit')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->authGuard('web')
            ->discoverResources(in: app_path('Filament/Unit/Resources'), for: 'App\\Filament\\Unit\\Resources')
            ->discoverPages(in: app_path('Filament/Unit/Pages'), for: 'App\\Filament\\Unit\\Pages')
            ->pages([
                Dashboard::class,
            ])

            ->discoverWidgets(in: app_path('Filament/Unit/Widgets'), for: 'App\\Filament\\Unit\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                // \App\Filament\Unit\Widgets\UnitReportStatsWidget::class,
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
                UnitMiddleware::class, // GUNAKAN CUSTOM MIDDLEWARE
            ])
            ->navigationGroups([
                // ✅ HAPUS ICON DARI GROUP, BIARKAN ITEMS YANG PUNYA ICON
                NavigationGroup::make('Monitoring')
                    ->label('Monitoring'),
                NavigationGroup::make('Management')
                    ->label('Management'),
                NavigationGroup::make('Reports')
                    ->label('Reports'),
            ])
            ->brandName('Unit Monitoring')
            ->favicon(asset('favicon.ico'));
    }
}
