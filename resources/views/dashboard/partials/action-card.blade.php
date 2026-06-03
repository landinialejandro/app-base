{{-- FILE: resources/views/dashboard/partials/action-card.blade.php | V1 --}}

<a href="{{ $card['route'] }}" class="dashboard-link-card dashboard-module-card dashboard-module-card--{{ $card['module'] }}">
    <span class="dashboard-module-icon">
        <x-dynamic-component :component="'icons.' . $card['icon']" />
    </span>

    <span class="dashboard-module-watermark">
        <x-dynamic-component :component="'icons.' . $card['icon']" />
    </span>

    <span class="dashboard-link-title">{{ $card['title'] }}</span>
    <span class="dashboard-link-text">{{ $card['text'] }}</span>
    <span class="dashboard-link-meta">{{ $card['meta'] }}</span>
</a>
