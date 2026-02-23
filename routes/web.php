<?php
// routes/web.php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\DeletionController;
use App\Http\Controllers\UserDeletionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RedirectAuthenticatedUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

// Landing page
Route::get('/', function () {
    return view('welcome');
})->name('landing');

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación Personalizadas
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    // Registro
    Route::get('/app/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/app/register', [RegisterController::class, 'register']);
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Invitaciones (acceso público con token)
|--------------------------------------------------------------------------
*/

Route::get('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Requieren Autenticación)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    
    // Invitaciones
    Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    
    /*
    |--------------------------------------------------------------------------
    | Solicitudes de Baja (Usuarios)
    |--------------------------------------------------------------------------
    */
    
    // Formulario y gestión de solicitudes de baja
    Route::get('/deletion/request', [UserDeletionController::class, 'showForm'])->name('deletion.form');
    Route::post('/deletion/request', [UserDeletionController::class, 'submitRequest'])->name('deletion.request');
    Route::post('/deletion/cancel', [UserDeletionController::class, 'cancelRequest'])->name('deletion.cancel');
    
    /*
    |--------------------------------------------------------------------------
    | Rutas para Administradores de Organización
    |--------------------------------------------------------------------------
    */
    
    Route::middleware(['role:admin'])->group(function () {
        // Aprobación/rechazo de bajas de usuarios
        Route::post('/deletion/approve/{user}', [DeletionController::class, 'approve'])->name('deletion.approve');
        Route::post('/deletion/reject/{user}', [DeletionController::class, 'reject'])->name('deletion.reject');
        
        // Cierre de organización (solo el creador/admin principal)
        Route::post('/organization/{organization}/close', [DeletionController::class, 'closeOrganization'])
            ->name('organization.close');
    });
});

/*
|--------------------------------------------------------------------------
| Redirección por rol (Mantener por compatibilidad)
|--------------------------------------------------------------------------
|
| Esta ruta puede ser útil si aún tienes referencias al controlador
| RedirectAuthenticatedUserController en algún lugar.
|
*/

Route::get('/redirect', [RedirectAuthenticatedUserController::class, 'redirect'])
    ->name('redirect')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| REDIRECCIONES DE FILAMENT A LOGIN PERSONALIZADO
|--------------------------------------------------------------------------
|
| Estas rutas capturan cualquier intento de acceder a los logins nativos
| de Filament y los redirigen a nuestro login unificado en /login
|
*/

// Panel Superadmin
Route::get('/super/login', function () {
    return redirect()->route('login');
})->name('filament.super.login');

// Panel Admin
Route::get('/admin/login', function () {
    return redirect()->route('login');
})->name('filament.admin.login');

// Panel App (usuarios normales)
Route::get('/app/login', function () {
    return redirect()->route('login');
})->name('filament.app.login');

// Redirecciones de registro de Filament (si existen)
Route::get('/super/register', function () {
    return redirect()->route('register');
});

Route::get('/admin/register', function () {
    return redirect()->route('register');
});

Route::get('/app/register', function () {
    // Ya tienes esta ruta definida arriba, pero por si acaso
    return redirect()->route('register');
});

/*
|--------------------------------------------------------------------------
| CATCH-ALL PARA OTROS PANELES (opcional)
|--------------------------------------------------------------------------
|
| Esta ruta capturará cualquier /{panel}/login que no haya sido
| capturado por las rutas específicas arriba.
|
*/

Route::get('/{panel}/login', function ($panel) {
    return redirect()->route('login');
})->where('panel', 'admin|super|app|user|dashboard'); // Ajusta según tus paneles

/*
|--------------------------------------------------------------------------
| HEALTH CHECK (mantener por compatibilidad con Laravel)
|--------------------------------------------------------------------------
*/

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});