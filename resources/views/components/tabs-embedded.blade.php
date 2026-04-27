{{-- FILE: resources/views/components/tabs-embedded.blade.php | V2 --}}

@props([
    'items' => collect(),
    'statuses' => [],
    'tabsId' => null,
    'toolbarLabel' => 'Estados',
    'tableView',
    'tableData' => [],
    'emptyMessage' => 'No hay registros.',
    'addUrl' => null,
    'addLabel' => 'Agregar',
    'summaryItems' => [],
    'extraHelp' => null,
])

@php
    $items = collect($items ?? [])->values();
    $statuses = collect($statuses ?? []);
    $summaryItems = collect($summaryItems ?? [])->values();
    $tableData = is_array($tableData ?? null) ? $tableData : [];
    $tabsId = $tabsId ?: 'tabs-embedded-' . uniqid();
@endphp

<div class="tabs" data-tabs>
    <x-tab-toolbar :label="$toolbarLabel">
        <x-slot:tabs>
            <x-horizontal-scroll :label="$toolbarLabel">
                <button type="button"
                    class="tabs-link is-active"
                    data-tab-link="{{ $tabsId }}-all"
                    role="tab"
                    aria-selected="true">
                    Todos
                    @if ($items->count())
                        ({{ $items->count() }})
                    @endif
                </button>

                @foreach ($statuses as $value => $label)
                    @php
                        $statusItems = $items->where('status', $value)->values();
                    @endphp

                    <button type="button"
                        class="tabs-link"
                        data-tab-link="{{ $tabsId }}-{{ $value }}"
                        role="tab"
                        aria-selected="false">
                        {{ $label }}
                        @if ($statusItems->count())
                            ({{ $statusItems->count() }})
                        @endif
                    </button>
                @endforeach
            </x-horizontal-scroll>
        </x-slot:tabs>

        @if ($addUrl)
            <x-slot:actions>
                <x-button-create :href="$addUrl" :label="$addLabel" class="btn-sm" />
            </x-slot:actions>
        @endif
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include($tableView, $tableData + [
                    'items' => $items,
                    'emptyMessage' => $emptyMessage,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($statuses as $value => $label)
        @php
            $statusItems = $items->where('status', $value)->values();
        @endphp

        <section class="tab-panel"
            data-tab-panel="{{ $tabsId }}-{{ $value }}"
            hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include($tableView, $tableData + [
                        'items' => $statusItems,
                        'emptyMessage' => "No hay registros en estado {$label}.",
                    ])
                </x-card>
            </div>
        </section>
    @endforeach

    @if ($summaryItems->isNotEmpty() || $extraHelp)
        <x-card>
            @if ($summaryItems->isNotEmpty())
                <div class="summary-inline-grid">
                    @foreach ($summaryItems as $summaryItem)
                        <div class="summary-inline-card">
                            <div class="summary-inline-label">
                                {{ $summaryItem['label'] ?? 'Dato' }}
                            </div>

                            <div class="summary-inline-value">
                                {{ $summaryItem['value'] ?? '—' }}
                            </div>

                            @if (! empty($summaryItem['subvalue']))
                                <div class="form-help mt-1">
                                    {{ $summaryItem['subvalue'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($extraHelp)
                <div class="form-help mt-3">
                    {{ $extraHelp }}
                </div>
            @endif
        </x-card>
    @endif
</div>

<x-dev-component-version name="tabs-embedded" version="V2" />