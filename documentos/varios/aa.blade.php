\<x-page>
    <x-breadcrumb :items="$breadcrumbItems" />

    <x-page-header :title="$pageTitle">
        @foreach ($headerActions as $actionSurface)
            @include($actionSurface['view'], $actionSurface['data'] ?? [])
        @endforeach

        <a href="{{ $backUrl }}" class="btn btn-secondary btn-icon" title="Volver" aria-label="Volver">
            <x-icons.chevron-left />
        </a>
    </x-page-header>

    <x-show-summary details-id="{{ $detailsId }}">
        @foreach ($summaryItems as $summarySurface)
            <x-show-summary-item :label="$summarySurface['label'] ?? 'Relacionado'">
                @include($summarySurface['view'], $summarySurface['data'] ?? [])
            </x-show-summary-item>
        @endforeach

        @foreach ($staticSummaryItems as $item)
            <x-show-summary-item :label="$item['label']">
                {!! $item['content'] !!}
            </x-show-summary-item>
        @endforeach

        <x-slot:details>
            @foreach ($detailItems as $detailSurface)
                <x-show-summary-item-detail-block :label="$detailSurface['label'] ?? 'Detalle'" :full="$detailSurface['full'] ?? false">
                    @include($detailSurface['view'], $detailSurface['data'] ?? [])
                </x-show-summary-item-detail-block>
            @endforeach

            @foreach ($staticDetailItems as $item)
                <x-show-summary-item-detail-block :label="$item['label']" :full="$item['full'] ?? false">
                    {!! $item['content'] !!}
                </x-show-summary-item-detail-block>
            @endforeach
        </x-slot:details>
    </x-show-summary>

    @if (!empty($tabSurfaces))
        <div class="tabs" data-tabs>
            <x-tab-toolbar :label="$tabsLabel">
                <x-slot:tabs>
                    <x-horizontal-scroll :label="$tabsLabel">
                        @foreach ($tabSurfaces as $tabSurface)
                            <button type="button"
                                class="tabs-link {{ ($defaultTab ?? null) === $tabSurface['key'] ? 'is-active' : '' }}"
                                data-tab-link="{{ $tabSurface['key'] }}" role="tab"
                                aria-selected="{{ ($defaultTab ?? null) === $tabSurface['key'] ? 'true' : 'false' }}">
                                {{ $tabSurface['label'] ?? $tabSurface['key'] }}

                                @if (array_key_exists('count', $tabSurface) && (int) $tabSurface['count'] > 0)
                                    ({{ $tabSurface['count'] }})
                                @endif
                            </button>
                        @endforeach
                    </x-horizontal-scroll>
                </x-slot:tabs>

                @isset($tabToolbarActions)
                    <x-slot:actions>
                        @foreach ($tabToolbarActions as $actionSurface)
                            @include($actionSurface['view'], $actionSurface['data'] ?? [])
                        @endforeach
                    </x-slot:actions>
                @endisset
            </x-tab-toolbar>

            @foreach ($tabSurfaces as $tabSurface)
                <section class="tab-panel {{ ($defaultTab ?? null) === $tabSurface['key'] ? 'is-active' : '' }}"
                    data-tab-panel="{{ $tabSurface['key'] }}" @if (($defaultTab ?? null) !== $tabSurface['key']) hidden @endif>
                    <div class="tab-panel-stack">
                        @include($tabSurface['view'], $tabSurface['data'] ?? [])
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-page>
