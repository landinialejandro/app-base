<?php
// app/Filament/Super/Pages/Dashboard.php

namespace App\Filament\Super\Pages;

use Filament\Pages\Page;

class Dashboard extends Page {
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.super.pages.dashboard';

    protected static ?string $title = 'Dashboard Super Admin';

    protected static ?int $navigationSort = 1;

    protected function getWidgets(): array {
        return [
            \App\Filament\Super\Widgets\StatsOverview::class,
            \App\Filament\Super\Widgets\TopOrganizations::class,
        ];
    }

    protected function getColumns(): int | array {
        return 2;
    }
}
