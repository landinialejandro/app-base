{{-- FILE: resources/views/parties/show.blade.php | V15 --}}

@extends('layouts.app')

@section('title', 'Detalle del contacto')

@section('content')
    @php
        use App\Support\Catalogs\PartyCatalog;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Parties\PartySurfaceService;

        $pageTitle = 'Detalle del contacto';
        $detailsId = 'party-detail-panel';
        $tabsLabel = 'Relaciones del contacto';

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('parties.index'));

        $hostPack = app(PartySurfaceService::class)->hostPack('parties.show', $party, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('parties.show', $hostPack))->values();
        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('parties.show', $hostPack))->values();

        $headerActions = $linked->where('slot', 'header_actions')->values();
        $summaryItems = $linked->where('slot', 'summary_items')->values();
        $detailItems = $embedded->where('slot', 'detail_items')->values();

        $tabItems = $embedded
            ->where(fn($item) => ($item['slot'] ?? null) === 'tab_panels')
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$pageTitle">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @can('update', $party)
                <x-button-edit :href="route('parties.edit', ['party' => $party] + $trailQuery)" />
            @endcan

            @can('delete', $party)
                <x-button-delete :action="route('parties.destroy', ['party' => $party] + $trailQuery)" message="¿Eliminar contacto?" />
            @endcan

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="{{ $detailsId }}">
            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-show-summary-item label="Nombre">
                {{ $party->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Teléfono">
                {{ $party->phone ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Email">
                {{ $party->email ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
                @foreach ($detailItems as $detailItem)
                    <x-show-summary-item-detail-block :label="$detailItem['label'] ?? 'Relacionado'">
                        @include($detailItem['view'], $detailItem['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

                <x-show-summary-item-detail-block label="Tipo">
                    {{ PartyCatalog::label($party->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Nombre visible">
                    {{ $party->display_name ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Tipo documento">
                    {{ $party->document_type ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Número documento">
                    {{ $party->document_number ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="CUIT / Tax ID">
                    {{ $party->tax_id ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Activo">
                    <span class="status-badge {{ $party->is_active ? 'status-badge--done' : 'status-badge--cancelled' }}">
                        {{ $party->is_active ? 'Sí' : 'No' }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Dirección" full>
                    {{ $party->address ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado">
                    {{ $party->created_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $party->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $party->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        @if ($tabItems->isNotEmpty())
            <div class="tabs" data-tabs">
                <x-tab-toolbar :label="$tabsLabel">
                    <x-slot:tabs>
                        <x-horizontal-scroll :label="$tabsLabel">
                            @foreach ($tabItems as $tabItem)
                                <button type="button" class="tabs-link {{ $loop->first ? 'is-active' : '' }}"
                                    data-tab-link="{{ $tabItem['key'] }}" role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $tabItem['label'] ?? $tabItem['key'] }}

                                    @if (array_key_exists('count', $tabItem) && (int) $tabItem['count'] > 0)
                                        ({{ $tabItem['count'] }})
                                    @endif
                                </button>
                            @endforeach
                        </x-horizontal-scroll>
                    </x-slot:tabs>
                </x-tab-toolbar>

                @foreach ($tabItems as $tabItem)
                    <section class="tab-panel {{ $loop->first ? 'is-active' : '' }}"
                        data-tab-panel="{{ $tabItem['key'] }}" @unless ($loop->first) hidden @endunless>
                        <div class="tab-panel-stack">
                            @include($tabItem['view'], $tabItem['data'] ?? [])
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-page>
@endsection
