<?php
// app/Filament/Super/Widgets/TopOrganizations.php

namespace App\Filament\Super\Widgets;

use App\Models\Organization;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Actions\Action;

class TopOrganizations extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Top 5 Organizaciones por cantidad de usuarios';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Organization::query()
                    ->withCount('users')
                    ->orderBy('users_count', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Organización')
                    ->weight('bold')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios totales')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('pending_count')
                    ->label('Pendientes')
                    ->getStateUsing(fn ($record) => 
                        $record->users()->whereNull('approved_at')->count()
                    )
                    ->badge()
                    ->color('warning'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->actions([
                // USAR EDIT EN LUGAR DE VIEW
                Action::make('edit')
                    ->label('Editar')
                    ->url(fn ($record) => url("/super/organizations/{$record->id}/edit"))
                    ->icon('heroicon-o-pencil')
                    ->color('warning'),
                    
                // O si prefieres ver el detalle, podemos agregar un enlace al nombre
            ]);
    }
}