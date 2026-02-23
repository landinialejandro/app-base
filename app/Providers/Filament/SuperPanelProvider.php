<?php
// app/Providers/Filament/SuperPanelProvider.php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class SuperPanelProvider extends PanelProvider {
    public function panel(Panel $panel): Panel {
        return $panel
            ->id('super')
            ->path('super')
            ->colors([
                'primary' => '#7C3AED', // Púrpura para diferenciar
            ])
            ->brandName('Super Admin')
            ->pages([
                \App\Filament\Super\Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Super\Widgets\StatsOverview::class,
                \App\Filament\Super\Widgets\TopOrganizations::class,
            ])
            ->navigationGroups([
                'Organizaciones',
                'Usuarios',
                'Estadísticas',
                'Auditoría',
            ])
            ->discoverResources(in: app_path('Filament/Super/Resources'), for: 'App\\Filament\\Super\\Resources')
            ->discoverPages(in: app_path('Filament/Super/Pages'), for: 'App\\Filament\\Super\\Pages')
            ->discoverWidgets(in: app_path('Filament/Super/Widgets'), for: 'App\\Filament\\Super\\Widgets')
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
                'superadmin', // Middleware personalizado que crearemos
            ]);
    }
}
