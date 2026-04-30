<?php

// FILE: routes/web.php | V5

use App\Http\Controllers\AdminInvitationController;
use App\Http\Controllers\AdminMetricsController;
use App\Http\Controllers\AdminSignupRequestController;
use App\Http\Controllers\AdminTenantController;
use App\Http\Controllers\AdminTenantModuleAccessController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentItemController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvitationAcceptanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicSignupRequestController;
use App\Http\Controllers\SuperadminDashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TechnicalDocController;
use App\Http\Controllers\TenantInvitationController;
use App\Http\Controllers\TenantMembershipController;
use App\Http\Controllers\TenantMembershipPartyController;
use App\Http\Controllers\TenantMembershipRoleController;
use App\Http\Controllers\TenantProfileController;
use App\Http\Controllers\TenantProfilePermissionController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.home');
})->name('landing.home');

Route::get('/pricing', function () {
    return view('landing.pricing');
})->name('landing.pricing');

Route::get('/solicitar-empresa', [PublicSignupRequestController::class, 'create'])
    ->name('public.signup-requests.create');

Route::post('/solicitar-empresa', [PublicSignupRequestController::class, 'store'])
    ->name('public.signup-requests.store');

Route::view('/solicitud-enviada', 'public.signup-requests.thank-you')
    ->name('public.signup-requests.thank-you');

Route::get('/docs', [TechnicalDocController::class, 'index'])->name('docs.index');
Route::get('/docs/{slug}', [TechnicalDocController::class, 'show'])->name('docs.show');
Route::put('/docs/{slug}/sections/{section}', [TechnicalDocController::class, 'updateSection'])
    ->name('docs.sections.update');

// Selector de empresa
Route::middleware('auth')->get('/tenants/select', function () {
    $tenants = Auth::user()->tenants()
        ->wherePivot('status', 'active')
        ->select('tenants.id', 'tenants.name', 'tenants.slug')
        ->get();

    return view('tenants.select', compact('tenants'));
})->name('tenants.select');

Route::middleware('auth')->post('/tenants/select/{tenant}', function (Tenant $tenant) {
    $user = Auth::user();

    $allowed = $user->memberships()
        ->where('tenant_id', $tenant->id)
        ->where('status', 'active')
        ->exists();

    if (! $allowed) {
        abort(403, 'You do not have active access to this tenant.');
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

Route::middleware(['auth', 'superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [SuperadminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/tenants', [AdminTenantController::class, 'index'])
            ->name('tenants.index');
        Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show'])
            ->name('tenants.show');
        Route::get('/tenants/{tenant}/modules', [AdminTenantModuleAccessController::class, 'edit'])
            ->name('tenants.modules.edit');
        Route::put('/tenants/{tenant}/modules', [AdminTenantModuleAccessController::class, 'update'])
            ->name('tenants.modules.update');
        Route::delete('/tenants/{tenant}/modules', [AdminTenantModuleAccessController::class, 'reset'])
            ->name('tenants.modules.reset');

        Route::get('/signup-requests', [AdminSignupRequestController::class, 'index'])
            ->name('signup-requests.index');
        Route::get('/signup-requests/processed', [AdminSignupRequestController::class, 'processed'])
            ->name('signup-requests.processed');
        Route::get('/signup-requests/{signupRequest}', [AdminSignupRequestController::class, 'show'])
            ->name('signup-requests.show');
        Route::post('/signup-requests/{signupRequest}/approve', [AdminSignupRequestController::class, 'approve'])
            ->name('signup-requests.approve');
        Route::post('/signup-requests/{signupRequest}/reject', [AdminSignupRequestController::class, 'reject'])
            ->name('signup-requests.reject');

        Route::get('/invitations/owner-signups', [AdminInvitationController::class, 'ownerSignups'])
            ->name('invitations.owner-signups');
        Route::post('/invitations/{invitation}/mark-as-sent', [AdminInvitationController::class, 'markAsSent'])
            ->name('invitations.mark-as-sent');
        Route::get('/metrics/owners', [AdminMetricsController::class, 'owners'])
            ->name('metrics.owners');
        Route::get('/metrics/tenants', [AdminMetricsController::class, 'tenants'])
            ->name('metrics.tenants');
    });

Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('profile.show');

Route::get('/profile/tenant', [ProfileController::class, 'showTenant'])
    ->middleware(['auth', 'tenant'])
    ->name('profile.tenant.show');

Route::middleware(['auth', 'tenant'])->group(function () {

    Route::get('/tenant/profile', [TenantProfileController::class, 'show'])
        ->name('tenant.profile.show');
    Route::put('/tenant/profile', [TenantProfileController::class, 'update'])
        ->name('tenant.profile.update');
    Route::put('/tenant/profile/permissions', [TenantProfilePermissionController::class, 'update'])
        ->name('tenant.profile.permissions.update');

    Route::post('/tenant/memberships/{membership}/block', [TenantMembershipController::class, 'block'])
        ->name('tenant.memberships.block');
    Route::post('/tenant/memberships/{membership}/unblock', [TenantMembershipController::class, 'unblock'])
        ->name('tenant.memberships.unblock');
    Route::post('/tenant/memberships/{membership}/roles', [TenantMembershipRoleController::class, 'attach'])
        ->name('tenant.memberships.roles.attach');
    Route::delete('/tenant/memberships/{membership}/roles/{role}', [TenantMembershipRoleController::class, 'detach'])
        ->name('tenant.memberships.roles.detach');

    Route::post('/tenant/invitations', [TenantInvitationController::class, 'store'])
        ->name('tenant.invitations.store');
    Route::delete('/tenant/invitations/{invitation}', [TenantInvitationController::class, 'destroy'])
        ->name('tenant.invitations.destroy');

    Route::post('/profile/party', [TenantMembershipPartyController::class, 'current'])
        ->name('profile.party');
    Route::post('/tenant/memberships/{membership}/party', [TenantMembershipPartyController::class, 'show'])
        ->name('tenant.memberships.party');
    Route::get('/tenant/memberships/{membership}/party/confirm', [TenantMembershipPartyController::class, 'confirm'])
        ->name('tenant.memberships.party.confirm');

    Route::post('/tenant/memberships/{membership}/party/store', [TenantMembershipPartyController::class, 'store'])
        ->name('tenant.memberships.party.store');
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Parties
    Route::get('/parties', [PartyController::class, 'index'])->name('parties.index');
    Route::get('/parties/create', [PartyController::class, 'create'])->name('parties.create');
    Route::post('/parties', [PartyController::class, 'store'])->name('parties.store');
    Route::get('/parties/{party}', [PartyController::class, 'show'])->name('parties.show');
    Route::get('/parties/{party}/edit', [PartyController::class, 'edit'])->name('parties.edit');
    Route::put('/parties/{party}', [PartyController::class, 'update'])->name('parties.update');
    Route::delete('/parties/{party}', [PartyController::class, 'destroy'])->name('parties.destroy');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Assets
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/create', [AssetController::class, 'create'])->name('assets.create');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
    Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])
        ->name('orders.status.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    Route::post('/orders/{order}/documents', [DocumentController::class, 'storeFromOrder'])
        ->name('orders.documents.store');

    // Order items
    Route::get('/orders/{order}/items/create', [OrderItemController::class, 'create'])
        ->name('orders.items.create');
    Route::post('/orders/{order}/items', [OrderItemController::class, 'store'])
        ->name('orders.items.store');
    Route::get('/orders/{order}/items/{item}/edit', [OrderItemController::class, 'edit'])
        ->name('orders.items.edit');
    Route::put('/orders/{order}/items/{item}', [OrderItemController::class, 'update'])
        ->name('orders.items.update');
    Route::delete('/orders/{order}/items/{item}', [OrderItemController::class, 'destroy'])
        ->name('orders.items.destroy');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::post('/documents/{document}/status', [DocumentController::class, 'updateStatus'])
        ->name('documents.status.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::prefix('documents/{document}')->name('documents.')->group(function () {
        Route::get('items/create', [DocumentItemController::class, 'create'])->name('items.create');
        Route::post('items', [DocumentItemController::class, 'store'])->name('items.store');
        Route::get('items/{item}/edit', [DocumentItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [DocumentItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [DocumentItemController::class, 'destroy'])->name('items.destroy');
    });

    // Attachments
    Route::get('/attachments/create', [AttachmentController::class, 'create'])
        ->name('attachments.create');
    Route::post('/attachments', [AttachmentController::class, 'store'])
        ->name('attachments.store');
    Route::get('/attachments/{attachment}/edit', [AttachmentController::class, 'edit'])
        ->name('attachments.edit');
    Route::put('/attachments/{attachment}', [AttachmentController::class, 'update'])
        ->name('attachments.update');
    Route::get('/attachments/{attachment}/preview', [AttachmentController::class, 'preview'])
        ->name('attachments.preview');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');

    // Inventory Fase 2
    Route::get('/inventory', [InventoryController::class, 'index'])
        ->name('inventory.index');
    Route::get('/inventory/movements/{movement}', [InventoryController::class, 'showMovement'])
        ->name('inventory.movements.show');
    Route::get('/inventory/{product}', [InventoryController::class, 'show'])
        ->name('inventory.show');
    Route::get('/inventory/{product}/movements/create', [InventoryController::class, 'createMovement'])
        ->name('inventory.movements.create');
    Route::post('/inventory/movements', [InventoryController::class, 'storeMovement'])
        ->name('inventory.movements.store');
    Route::post('/inventory/orders/{order}/items/{item}/return', [InventoryController::class, 'returnOrderItemQuantity'])
        ->name('inventory.order-items.return');
    Route::post('/inventory/documents/{document}/items/{item}/execute', [InventoryController::class, 'executeDocumentItem'])
        ->name('inventory.document-items.execute');
    Route::post('/inventory/documents/{document}/items/{item}/return', [InventoryController::class, 'returnDocumentItemQuantity'])
        ->name('inventory.document-items.return');

    // Print
    Route::get('/appointments/{appointment}/pdf', [AppointmentController::class, 'pdf'])
        ->name('appointments.pdf');
    Route::get('/orders/{order}/pdf', [OrderController::class, 'pdf'])
        ->name('orders.pdf');
    Route::get('/documents/{document}/pdf', [DocumentController::class, 'pdf'])
        ->name('documents.pdf');

    Route::get('/appointments/{appointment}/print', [AppointmentController::class, 'print'])
        ->name('appointments.print');
    Route::get('/orders/{order}/print', [OrderController::class, 'print'])
        ->name('orders.print');
    Route::get('/documents/{document}/print', [DocumentController::class, 'print'])
        ->name('documents.print');
});
