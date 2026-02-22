<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\DeletionController;
use App\Http\Controllers\UserDeletionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
});

Route::get('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware(['auth'])->group(function () {
    // Usuario solicita su baja
    Route::post('/deletion/request', [DeletionController::class, 'request'])
        ->name('deletion.request');

    // Admin aprueba/rechaza bajas
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/deletion/approve/{user}', [DeletionController::class, 'approve'])
            ->name('deletion.approve');
        Route::post('/deletion/reject/{user}', [DeletionController::class, 'reject'])
            ->name('deletion.reject');
    });

    // Cierre de empresa (solo creador)
    Route::post('/organization/{organization}/close', [DeletionController::class, 'closeOrganization'])
        ->name('organization.close');
});

Route::middleware(['auth'])->group(function () {
    // Rutas para solicitud de baja
    Route::get('/deletion/request', [UserDeletionController::class, 'showForm'])
        ->name('deletion.form');
    Route::post('/deletion/request', [UserDeletionController::class, 'submitRequest'])
        ->name('deletion.request');
    Route::post('/deletion/cancel', [UserDeletionController::class, 'cancelRequest'])
        ->name('deletion.cancel');
});

