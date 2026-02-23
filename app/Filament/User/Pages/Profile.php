<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    protected static string $view = 'filament.user.pages.profile';
    
    protected static ?string $title = 'Mi Perfil';
    
    protected static ?int $navigationSort = 1;
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill([
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'organization_name' => auth()->user()->organization?->name,
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Información de Organización')
                    ->schema([
                        Forms\Components\TextInput::make('organization_name')
                            ->label('Organización')
                            ->disabled(fn () => auth()->user()->role !== 'admin')
                            ->helperText(fn () => auth()->user()->role === 'admin' 
                                ? 'Como admin puedes cambiar el nombre de la empresa' 
                                : 'Solo el admin puede cambiar el nombre de la empresa'),
                    ])->visible(fn () => !auth()->user()->is_platform_admin),
                    
                Forms\Components\Section::make('Cambiar Contraseña')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Contraseña actual')
                            ->password()
                            ->revealable()
                            ->requiredWith('new_password')
                            ->currentPassword(),
                        Forms\Components\TextInput::make('new_password')
                            ->label('Nueva contraseña')
                            ->password()
                            ->revealable()
                            ->confirmed(),
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirmar nueva contraseña')
                            ->password()
                            ->revealable(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        $user = auth()->user();
        
        // Actualizar datos básicos
        $user->name = $data['name'];
        $user->email = $data['email'];
        
        // Si es admin y cambió nombre de empresa
        if ($user->role === 'admin' && isset($data['organization_name'])) {
            $organization = $user->organization;
            if ($organization) {
                $organization->name = $data['organization_name'];
                $organization->save();
            }
        }
        
        // Cambiar contraseña si se proporcionó
        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }
        
        $user->save();
        
        Notification::make()
            ->title('Perfil actualizado')
            ->success()
            ->send();
    }
}