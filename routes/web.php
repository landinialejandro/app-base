<?php

// FILE: routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Http\Controllers\InvitationAcceptanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentItemController;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Membership;
use App\Models\Tenant;

Route::get('/', function () {
    return view('welcome');
});

// Debug API: tenant por header X-Tenant
Route::middleware('tenant')->get('/whoami', function () {
    $tenant = app('tenant');

    return [
        'tenant_id' => $tenant->id,
        'tenant_slug' => $tenant->slug,
    ];
});

// Selector de empresa
Route::middleware('auth')->get('/tenants/select', function () {
    $tenants = Auth::user()->tenants()
        ->select('tenants.id', 'tenants.name', 'tenants.slug')
        ->get();

    return view('tenants.select', compact('tenants'));
})->name('tenants.select');

Route::middleware('auth')->post('/tenants/select/{tenant}', function (Tenant $tenant) {
    $user = Auth::user();

    $allowed = $user->tenants()
        ->where('tenants.id', $tenant->id)
        ->exists();

    if (!$allowed) {
        abort(403, 'You are not a member of this tenant.');
    }

    session(['tenant_id' => $tenant->id]);

    return redirect()->route('dashboard');
})->name('tenants.select.store');

Route::middleware(['auth', 'tenant'])->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

// Aceptación real de invitación (transitorio, pendiente de profesionalizar)
Route::get('/accept-invitation/{token}', [InvitationAcceptanceController::class, 'show'])
    ->name('invitation.accept.show');

Route::post('/accept-invitation/{token}', [InvitationAcceptanceController::class, 'store'])
    ->name('invitation.accept.store');

Route::middleware(['auth', 'tenant'])->group(function () {

    Route::view('/profile', 'profile.show')->name('profile.show');

    Route::get('/tenant/profile', function () {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        return view('tenants.profile', [
            'tenant' => $tenant,
        ]);
    })->name('tenant.profile.show');

    Route::put('/tenant/profile', function (Request $request) {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $tenant->update($data);

        return redirect()
            ->route('tenant.profile.show')
            ->with('success', 'Perfil de empresa actualizado correctamente.');
    })->name('tenant.profile.update');

    Route::resource('projects', ProjectController::class);

    Route::resource('parties', PartyController::class);

    Route::resource('tasks', TaskController::class);

    Route::resource('products', ProductController::class);

    Route::resource('orders', OrderController::class);

    Route::resource('orders.items', OrderItemController::class)
        ->except(['index', 'show']);

    Route::resource('documents', DocumentController::class);

    Route::prefix('documents/{document}')->name('documents.')->group(function () {
        Route::get('items/create', [DocumentItemController::class, 'create'])->name('items.create');
        Route::post('items', [DocumentItemController::class, 'store'])->name('items.store');
        Route::get('items/{item}/edit', [DocumentItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [DocumentItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [DocumentItemController::class, 'destroy'])->name('items.destroy');
    });
});