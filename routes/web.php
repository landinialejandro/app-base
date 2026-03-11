<?php

// file: routes/web.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = (bool) $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()->withErrors([
                'email' => 'Las credenciales no son correctas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $tenantsCount = Auth::user()->tenants()->count();

        if ($tenantsCount > 1) {
            return redirect()->route('tenants.select');
        }

        if ($tenantsCount === 1) {
            $tenantId = Auth::user()->tenants()->value('tenants.id');
            session(['tenant_id' => $tenantId]);

            return redirect()->route('dashboard');
        }

        return redirect()->route('tenants.select');
    })->name('login.store');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::post('/register', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('tenants.select');
    })->name('register.store');
});

Route::middleware('auth')->post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->forget('tenant_id');
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Debug API: tenant por header X-Tenant
Route::middleware('tenant')->get('/whoami', function () {
    $tenant = app('tenant');

    return [
        'tenant_id' => $tenant->id,
        'tenant_slug' => $tenant->slug,
    ];
});

// Selector de empresa
Route::middleware(['auth'])->get('/tenants/select', function () {
    $tenants = Auth::user()->tenants()
        ->select('tenants.id', 'tenants.name', 'tenants.slug')
        ->get();

    return view('tenants.select', compact('tenants'));
})->name('tenants.select');

Route::middleware(['auth'])->post('/tenants/select/{tenant}', function (Tenant $tenant) {
    $user = Auth::user();

    $allowed = $user->tenants()->where('tenants.id', $tenant->id)->exists();

    if (!$allowed) {
        abort(403, 'You are not a member of this tenant.');
    }

    session(['tenant_id' => $tenant->id]);

    return redirect()->route('dashboard');
})->name('tenants.select.store');

Route::middleware(['auth', 'tenant'])->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

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

Route::middleware(['auth', 'tenant'])->group(function () {

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