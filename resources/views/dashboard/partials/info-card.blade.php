{{-- FILE: resources/views/dashboard/partials/info-card.blade.php | V1 --}}

<div
    class="dashboard-info-card dashboard-module-card dashboard-module-card--accent-{{ $card['accent'] }} dashboard-module-card--module-{{ $card['module'] }}">
    <span class="dashboard-module-icon">
        <x-dynamic-component :component="'icons.' . $card['icon']" />
    </span>

    <span class="dashboard-module-watermark">
        <x-dynamic-component :component="'icons.' . $card['icon']" />
    </span>

    <span class="dashboard-info-title">{{ $card['title'] }}</span>
    <span class="dashboard-info-text">{{ $card['text'] }}</span>
    <span class="dashboard-info-meta">{{ $card['meta'] }}</span>
    <x-dev-component-version name="info-card" version="V1" align="right" />
</div>
