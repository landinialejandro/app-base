{{-- FILE: resources/views/dashboard/partials/info-card.blade.php | V1 --}}

<div class="dashboard-info-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
    <span class="dashboard-module-icon">
        <x-dynamic-component :component="'icons.' . $card['icon']" />
    </span>

    <span class="dashboard-module-watermark">
        <x-dynamic-component :component="'icons.' . $card['icon']" />
    </span>

    <span class="dashboard-info-title">{{ $card['title'] }}</span>
    <span class="dashboard-info-text">{{ $card['text'] }}</span>
    <span class="dashboard-info-meta">{{ $card['meta'] }}</span>
</div>
