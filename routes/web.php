<?php

//file: routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Http\Controllers\ProjectController;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Membership;
use App\Models\Tenant;
use App\Models\Project;

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

// Selector de tenant (web)
Route::middleware(['auth'])->get('/tenants/select', function () {
    $tenants = Auth::user()->tenants()
        ->select('tenants.id', 'tenants.name', 'tenants.slug')
        ->get();

    return view('tenants.select', compact('tenants'));
})->name('tenants.select');

Route::middleware(['auth','tenant'])->get('/dashboard', function () {
    $tenant = app('tenant');

    return view('dashboard', [
        'tenant' => $tenant,
        'projectsCount' => Project::count(),
    ]);
})->name('dashboard');

Route::middleware(['auth'])->post('/tenants/select/{tenant}', function (Tenant $tenant) {
    $user = Auth::user();

    $allowed = $user->tenants()->where('tenants.id', $tenant->id)->exists();
    if (!$allowed) {
        abort(403, 'You are not a member of this tenant.');
    }

    session(['tenant_id' => $tenant->id]);

    return redirect()->route('dashboard');

})->name('tenants.select.store');

// Aceptación real de invitación (dejamos UNA sola)
Route::get('/accept-invitation/{token}', function ($token) {
    $inv = Invitation::where('token', $token)->firstOrFail();

    if ($inv->accepted_at) {
        abort(409, 'Invitation already accepted');
    }

    if ($inv->expires_at && $inv->expires_at->isPast()) {
        abort(410, 'Invitation expired');
    }

    $user = User::where('email', $inv->email)->first();

    if (!$user) {
        $user = User::create([
            'name' => 'Invited User',
            'email' => $inv->email,
            'password' => Hash::make(Str::random(16)),
        ]);
    }

    $membership = Membership::firstOrCreate(
        ['tenant_id' => $inv->tenant_id, 'user_id' => $user->id],
        ['status' => 'active', 'joined_at' => now()]
    );

    $inv->update([
        'accepted_at' => now(),
        'accepted_ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]);

    return [
        'user_id' => $user->id,
        'membership_id' => $membership->id,
        'tenant_id' => $inv->tenant_id,
    ];
});

Route::middleware(['auth','tenant'])->group(function () {

    Route::get('/projects', [ProjectController::class,'index'])->name('projects.index');

    Route::get('/projects/create', [ProjectController::class,'create'])->name('projects.create');

    Route::post('/projects', [ProjectController::class,'store'])->name('projects.store');

    Route::get('/projects/{project}', [ProjectController::class,'show'])->name('projects.show');

    Route::get('/projects/{project}/edit', [ProjectController::class,'edit'])->name('projects.edit');

    Route::put('/projects/{project}', [ProjectController::class,'update'])->name('projects.update');

    Route::delete('/projects/{project}', [ProjectController::class,'destroy'])->name('projects.destroy');

});

