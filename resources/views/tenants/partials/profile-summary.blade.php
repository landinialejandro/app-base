{{-- FILE: resources/views/tenants/partials/profile-summary.blade.php --}}

<div class="summary-inline-grid">
    <div class="summary-inline-card">
        <div class="summary-inline-label">Empresa</div>
        <div class="summary-inline-value">{{ $tenant->name }}</div>
    </div>

    <div class="summary-inline-card">
        <div class="summary-inline-label">Slug</div>
        <div class="summary-inline-value">{{ $tenant->slug }}</div>
    </div>

    <div class="summary-inline-card">
        <div class="summary-inline-label">ID</div>
        <div class="summary-inline-value">{{ $tenant->id }}</div>
    </div>
</div>
