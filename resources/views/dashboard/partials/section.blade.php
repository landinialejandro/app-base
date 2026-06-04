{{-- FILE: resources/views/dashboard/partials/section.blade.php | V1 --}}

@if (($section['type'] ?? null) === 'partial')
    @include($section['partial'], $section['payload'] ?? [])
@elseif (($section['type'] ?? null) === 'cards')
    <x-card>
        <div class="content-section-header">
            <h2 class="content-section-title">{{ $section['title'] }}</h2>
            <p class="content-section-text">{{ $section['text'] }}</p>
        </div>

        <div class="dashboard-grid dashboard-grid--{{ $section['variant'] }}">
            @foreach ($section['info_cards'] as $card)
                @include('dashboard.partials.info-card', ['card' => $card])
            @endforeach

            @foreach ($section['action_cards'] as $card)
                @include('dashboard.partials.action-card', ['card' => $card])
            @endforeach
        </div>
        <x-dev-component-version name="section" version="V1" align="right" />
    </x-card>
@endif
