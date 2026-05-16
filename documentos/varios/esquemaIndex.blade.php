{{-- FILE: documentos/varios/esquemaIndex.blade.php | V1 --}}

{{-- 
    indexContext debe generarse preferentemente en una clase Support del módulo.

    Ejemplo:
    app/Support/Orders/OrderIndexContext.php

    Responsabilidad:
    - preparar título
    - preparar breadcrumb
    - preparar rutas contextuales
    - preparar labels
    - preparar flags visuales
    - preparar trail/query contextual
    - preparar payload de creación/listado cuando corresponda

    No debe:
    - autorizar
    - consultar registros principales
    - paginar
    - calcular dominio complejo
    - reemplazar controller, policy, Security ni services del módulo dueño
--}}

<x-page>
    <x-breadcrumb :items="$indexContext['breadcrumbItems']" />

    <x-page-header :title="$indexContext['pageTitle']">
        {{-- loop: header_actions --}}
        {{-- loop: surfaces.header_actions --}}
    </x-page-header>

    {{-- optional lightweight summary --}}
    @if (($indexContext['summaryEnabled'] ?? false) === true)
        <x-card>
            <div class="index-summary-grid">
                {{-- loop: summary_items --}}
                {{-- loop: surfaces.summary_items --}}
            </div>
        </x-card>
    @endif

    {{-- filters/search --}}
    @if (($indexContext['filtersEnabled'] ?? true) === true)
        <x-list-filters-card :action="$indexContext['indexUrl']" secondary-id="{{ $indexContext['filtersSecondaryId'] }}">
            <x-slot:primary>
                {{-- loop: primary_filter_items --}}
                {{-- loop: surfaces.primary_filter_items --}}
            </x-slot:primary>

            <x-slot:secondary>
                {{-- loop: secondary_filter_items --}}
                {{-- loop: surfaces.secondary_filter_items --}}
            </x-slot:secondary>
        </x-list-filters-card>
    @endif

    {{-- optional contextual toolbar --}}
    @if (($indexContext['toolbarEnabled'] ?? false) === true)
        <x-tab-toolbar :label="$indexContext['toolbarLabel']">
            {{-- loop: toolbar_items --}}
            {{-- loop: surfaces.toolbar_items --}}
        </x-tab-toolbar>
    @endif

    {{-- dominant index content --}}
    <x-card class="list-card">
        {{-- include: module.partials.table --}}
        {{-- table partial owns table markup --}}
        {{-- index passes prepared payload only --}}

        {{-- pagination --}}
    </x-card>
</x-page>
