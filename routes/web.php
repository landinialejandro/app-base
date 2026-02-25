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
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
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

Route::get('/admin/register', [RegisterController::class, 'showAdminRegistrationForm'])->name('admin.register');
Route::post('/admin/register', [RegisterController::class, 'registerAdmin']);

// Ruta de registro para usuarios de la aplicación
Route::get('/app/register', [RegisterController::class, 'showAppRegistrationForm'])->name('app.register');
Route::post('/app/register', [RegisterController::class, 'registerApp']);

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
| HEALTH CHECK (mantener por compatibilidad con Laravel)
|--------------------------------------------------------------------------
*/

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});
