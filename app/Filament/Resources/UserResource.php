<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class UserResource extends Resource {
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role')->badge(),
                Tables\Columns\TextColumn::make('deletion_requested_at')
                    ->label('Solicitó baja')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_request')
                    ->label('¿Solicitó baja?')
                    ->boolean()
                    ->getStateUsing(fn($record) => $record->hasRequestedDeletion()),
            ])
            ->filters([
                // FILTRO NUEVO: Usuarios con solicitud de baja
                Tables\Filters\Filter::make('pending_deletion')
                    ->label('Con solicitud de baja')
                    ->query(fn(Builder $query) => $query->withPendingDeletion())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    // ACCIONES NUEVAS: Aprobar/Rechazar baja
                    Tables\Actions\Action::make('approve_deletion')
                        ->label('Aprobar baja')
                        ->icon('heroicon-o-check')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->approveDeletion();
                            Filament::notify('success', 'Baja aprobada');
                        })
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record->hasRequestedDeletion()),

                    Tables\Actions\Action::make('reject_deletion')
                        ->label('Rechazar baja')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->action(function ($record) {
                            $record->rejectDeletion();
                            Filament::notify('success', 'Solicitud rechazada');
                        })
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record->hasRequestedDeletion()),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_bulk')
                    ->label('Aprobar seleccionados')
                    ->icon('heroicon-o-check')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->hasRequestedDeletion()) {
                                $record->approveDeletion();
                            }
                        }
                        Filament::notify('success', 'Bajas aprobadas');
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
