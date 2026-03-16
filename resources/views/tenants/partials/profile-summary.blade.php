{{-- FILE: resources/views/tenants/partials/profile-summary.blade.php --}}

<div class="summary-inline-grid mb-3">
    <div class="summary-inline-item">
        <span class="summary-inline-label">Empresa</span>
        <span class="summary-inline-value">{{ $tenant->name }}</span>
    </div>

    <div class="summary-inline-item">
        <span class="summary-inline-label">Slug</span>
        <span class="summary-inline-value">{{ $tenant->slug }}</span>
    </div>

    <div class="summary-inline-item">
        <span class="summary-inline-label">ID</span>
        <span class="summary-inline-value">{{ $tenant->id }}</span>
    </div>
</div>
