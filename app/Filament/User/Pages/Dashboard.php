<?php
// app/Filament/User/Pages/Dashboard.php



namespace App\Filament\User\Pages;

use Filament\Pages\Page;    


class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.user.pages.dashboard';
    
    protected static ?string $title = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    public function getWidgets(): array
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            return [
                \App\Filament\User\Widgets\AdminStats::class,
                \App\Filament\User\Widgets\PendingUsers::class,
            ];
        }
        
        return [
            \App\Filament\User\Widgets\UserInfo::class,
        ];
    }
}