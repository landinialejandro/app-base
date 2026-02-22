<?php

namespace App\Filament\Super\Resources\DashboardResource\Pages;

use App\Filament\Super\Resources\DashboardResource;
use Filament\Resources\Pages\Page;

class Dashboard extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.super.resources.dashboard-resource.pages.dashboard';
}
