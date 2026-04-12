{{-- FILE: resources/views/documents/partials/embedded-tabs.blade.php | V10 --}}

@php
    use App\Models\Document;
    use App\Support\Catalogs\DocumentCatalog;
    use App\Support\Auth\Security;

    $documents = $documents ?? collect();

    $showParty = $showParty ?? true;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;

    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $allLabel = $allLabel ?? 'Todos';

    $kinds = [
        DocumentCatalog::KIND_QUOTE => DocumentCatalog::label(DocumentCatalog::KIND_QUOTE),
        DocumentCatalog::KIND_DELIVERY_NOTE => DocumentCatalog::label(DocumentCatalog::KIND_DELIVERY_NOTE),
        DocumentCatalog::KIND_INVOICE => DocumentCatalog::label(DocumentCatalog::KIND_INVOICE),
    ];

    $tabsId = $tabsId ?? 'documents-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
    $createBaseQuery = $createBaseQuery ?? [];

    $order = $order ?? null;

    $allowedCreateKinds = collect(array_keys($kinds))
        ->filter(
            fn(string $kind) => app(Security::class)->allows(auth()->user(), 'documents.create', Document::class, [
                'kind' => $kind,
            ]),
        )
        ->values();

    $canCreateDocuments = $allowedCreateKinds->isNotEmpty();
    $defaultCreateKind = $allowedCreateKinds->first();

    $quoteCount = $quoteCount ?? $documents->where('kind', DocumentCatalog::KIND_QUOTE)->count();
    $deliveryNoteCount = $deliveryNoteCount ?? $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->count();
    $invoiceCount = $invoiceCount ?? $documents->where('kind', DocumentCatalog::KIND_INVOICE)->count();
@endphp

<div class="tabs" data-tabs>
    @php
        $toolbarActions = null;
    @endphp

    @if ($canCreateDocuments)
        @php
            $toolbarActions = route(
                'documents.create',
                $createBaseQuery + $trailQuery + ['kind' => $defaultCreateKind],
            );
        @endphp
    @endif

    <x-tab-toolbar label="Tipos de documentos">
        <x-slot:tabs>
            <x-horizontal-scroll label="Tipos de documentos">
                <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
                    aria-selected="true">
                    {{ $allLabel }}
                    @if ($documents->count())
                        ({{ $documents->count() }})
                    @endif
                </button>

                @foreach ($kinds as $value => $label)
                    @php
                        $kindDocuments = $documents->where('kind', $value)->values();
                    @endphp

                    <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                        role="tab" aria-selected="false">
                        {{ $label }}
                        @if ($kindDocuments->count())
                            ({{ $kindDocuments->count() }})
                        @endif
                    </button>
                @endforeach
            </x-horizontal-scroll>
        </x-slot:tabs>

        <x-slot:actions>
            @if ($order)
                <form method="POST" action="{{ route('orders.documents.store', ['order' => $order] + $trailQuery) }}"
                    class="inline-form"
                    @if ($quoteCount > 0) data-action="app-confirm-submit"
                    data-confirm-message="Esta orden ya tiene {{ $quoteCount }} presupuesto(s) asociado(s). ¿Deseas crear otro?" @endif>
                    @csrf
                    <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_QUOTE }}">
                    <button type="submit" class="btn btn-secondary btn-sm">
                        {{ $quoteCount > 0 ? 'Otro presupuesto' : 'Crear presupuesto' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('orders.documents.store', ['order' => $order] + $trailQuery) }}"
                    class="inline-form"
                    @if ($deliveryNoteCount > 0) data-action="app-confirm-submit"
                    data-confirm-message="Esta orden ya tiene {{ $deliveryNoteCount }} remito(s) asociado(s). ¿Deseas crear otro?" @endif>
                    @csrf
                    <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_DELIVERY_NOTE }}">
                    <button type="submit" class="btn btn-secondary btn-sm">
                        {{ $deliveryNoteCount > 0 ? 'Otro remito' : 'Crear remito' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('orders.documents.store', ['order' => $order] + $trailQuery) }}"
                    class="inline-form"
                    @if ($invoiceCount > 0) data-action="app-confirm-submit"
                    data-confirm-message="Esta orden ya tiene {{ $invoiceCount }} factura(s) asociada(s). ¿Deseas crear otra?" @endif>
                    @csrf
                    <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_INVOICE }}">
                    <button type="submit" class="btn btn-secondary btn-sm">
                        {{ $invoiceCount > 0 ? 'Otra factura' : 'Crear factura' }}
                    </button>
                </form>
            @elseif ($toolbarActions)
                <a href="{{ $toolbarActions }}" class="btn btn-success btn-sm">
                    <x-icons.plus />
                    <span>Nuevo documento</span>
                </a>
            @endif
        </x-slot:actions>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('documents.partials.table', [
                    'documents' => $documents,
                    'showParty' => $showParty,
                    'showAsset' => $showAsset,
                    'showOrder' => $showOrder,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($kinds as $value => $label)
        @php
            $kindDocuments = $documents->where('kind', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('documents.partials.table', [
                        'documents' => $kindDocuments,
                        'showParty' => $showParty,
                        'showAsset' => $showAsset,
                        'showOrder' => $showOrder,
                        'emptyMessage' => "No hay documentos de tipo {$label} para mostrar.",
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
