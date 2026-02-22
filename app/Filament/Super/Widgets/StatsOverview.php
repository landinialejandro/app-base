<?php
// app/Filament/Super/Widgets/StatsOverview.php

namespace App\Filament\Super\Widgets;

use App\Models\User;
use App\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOrgs = Organization::count();
        $totalUsers = User::count();
        $activeOrgs = Organization::where('is_active', true)->count();
        $pendingUsers = User::whereNull('approved_at')->count();
        
        return [
            Stat::make('Organizaciones', $totalOrgs)
                ->description($activeOrgs . ' activas')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),
                
            Stat::make('Usuarios', $totalUsers)
                ->description($pendingUsers . ' pendientes')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }
    
    // Asegurar que el widget se vea
    protected function getColumns(): int
    {
        return 2;
    }
}