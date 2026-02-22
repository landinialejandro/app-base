<?php
// app/Filament/User/Widgets/AdminStats.php

namespace App\Filament\User\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends BaseWidget
{
    protected function getStats(): array
    {
        $organization = auth()->user()->organization;
        
        return [
            Stat::make('Total Usuarios', $organization->users()->count())
                ->description('En tu organización')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Pendientes', $organization->users()->whereNull('approved_at')->count())
                ->description('Por aprobar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}