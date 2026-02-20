<?php
// app/Filament/Widgets/PendingDeletions.php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingDeletions extends BaseWidget {
    protected function getStats(): array {
        $count = User::withPendingDeletion()->count();

        return [
            Stat::make('Solicitudes de baja pendientes', $count)
                ->description('Usuarios que esperan aprobación')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($count > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.users.index', ['tableFilters[pending_deletion][value]' => true])),
        ];
    }
}
