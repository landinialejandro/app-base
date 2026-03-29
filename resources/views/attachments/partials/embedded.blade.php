{{-- FILE: resources/views/attachments/partials/embedded.blade.php | V2 --}}

@php
    use App\Support\Catalogs\AttachmentCatalog;
    use App\Support\Navigation\NavigationTrail;

    $attachments = $attachments ?? collect();
    $attachableType = $attachableType ?? null;
    $attachableId = $attachableId ?? null;
    $trailQuery = $trailQuery ?? [];
    $tabsId = $tabsId ?? 'attachments-tabs-' . uniqid();
    $allLabel = $allLabel ?? 'Todos';
    $createLabel = $createLabel ?? 'Agregar adjunto';
    $kinds = AttachmentCatalog::kindLabels();

    $resolvedReturnTo =
        $returnTo ?? NavigationTrail::previousUrl(NavigationTrail::fromRequest(request()), url()->current());
@endphp

<div class="tabs" data-tabs>
    <x-tab-toolbar label="Tipos de adjuntos">
        <x-slot:tabs>
            <x-horizontal-scroll label="Tipos de adjuntos">
                <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
                    aria-selected="true">
                    {{ $allLabel }}
                    @if ($attachments->count())
                        ({{ $attachments->count() }})
                    @endif
                </button>

                @foreach ($kinds as $value => $label)
                    @php
                        $kindAttachments = $attachments->where('kind', $value)->values();
                    @endphp

                    <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                        role="tab" aria-selected="false">
                        {{ $label }}
                        @if ($kindAttachments->count())
                            ({{ $kindAttachments->count() }})
                        @endif
                    </button>
                @endforeach
            </x-horizontal-scroll>
        </x-slot:tabs>

        <x-slot:actions>
            @if ($attachableType && $attachableId)
                <a href="{{ route(
                    'attachments.create',
                    [
                        'attachable_type' => $attachableType,
                        'attachable_id' => $attachableId,
                        'return_to' => $resolvedReturnTo,
                    ] + $trailQuery,
                ) }}"
                    class="btn btn-success btn-sm">
                    <x-icons.plus />
                    <span>{{ $createLabel }}</span>
                </a>
            @endif
        </x-slot:actions>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('attachments.partials.table', [
                    'attachments' => $attachments,
                    'trailQuery' => $trailQuery,
                    'returnTo' => $resolvedReturnTo,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($kinds as $value => $label)
        @php
            $kindAttachments = $attachments->where('kind', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('attachments.partials.table', [
                        'attachments' => $kindAttachments,
                        'trailQuery' => $trailQuery,
                        'returnTo' => $resolvedReturnTo,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
