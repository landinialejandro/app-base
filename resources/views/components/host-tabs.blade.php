{{-- FILE: resources/views/components/host-tabs.blade.php | V2 --}}

@props([
    'items' => collect(),
    'activeTab' => null,
    'label' => 'Secciones',
])

@php
    $items = collect($items ?? [])->values();
@endphp

@if ($items->isNotEmpty())
    <div class="tabs" data-tabs data-persist-return-tab="1">
        <x-tab-toolbar :label="$label">
            <x-slot:tabs>
                <x-horizontal-scroll :label="$label">
                    @foreach ($items as $tabItem)
                        @php
                            $isActive = ($tabItem['key'] ?? null) === $activeTab;
                            $icon = $tabItem['icon'] ?? ($tabItem['module_icon'] ?? null);
                        @endphp

                        <button type="button" class="tabs-link {{ $isActive ? 'is-active' : '' }}"
                            data-tab-link="{{ $tabItem['key'] }}" role="tab"
                            aria-selected="{{ $isActive ? 'true' : 'false' }}">
                            @if ($icon)
                                <span class="tabs-link__icon" aria-hidden="true">
                                    <x-dynamic-component :component="'icons.' . $icon" />
                                </span>
                            @endif

                            <span>{{ $tabItem['label'] ?? $tabItem['key'] }}</span>

                            @if (array_key_exists('count', $tabItem) && (int) $tabItem['count'] > 0)
                                <span class="tabs-link__count">({{ $tabItem['count'] }})</span>
                            @endif
                        </button>
                    @endforeach
                </x-horizontal-scroll>
            </x-slot:tabs>
        </x-tab-toolbar>

        @foreach ($items as $tabItem)
            @php
                $isActive = ($tabItem['key'] ?? null) === $activeTab;
            @endphp

            <section class="tab-panel {{ $isActive ? 'is-active' : '' }}" data-tab-panel="{{ $tabItem['key'] }}"
                @unless ($isActive) hidden @endunless>
                <div class="tab-panel-stack">
                    @include($tabItem['view'], $tabItem['data'] ?? [])
                </div>
            </section>
        @endforeach
    </div>
@endif

<x-dev-component-version name="host-tabs" version="V2" />
