{{-- FILE: resources/views/docs/partials/section.blade.php | V3 --}}
@php
    $detailsId = 'doc-section-' . $section->anchor;
@endphp

<x-card id="{{ $section->anchor }}">
    <div class="card-header">
        <div class="card-header__main">
            <h2 class="card-title">{{ $section->name }}</h2>
        </div>

        <div class="card-toolbox">
            <button type="button" class="btn btn-secondary btn-sm" data-action="app-toggle-details"
                data-toggle-target="#{{ $detailsId }}" data-toggle-text-collapsed="Expandir"
                data-toggle-text-expanded="Contraer">
                Contraer
            </button>
        </div>
    </div>

    <div id="{{ $detailsId }}" class="card-body">
        <div class="content">
            {!! $section->html !!}
        </div>
    </div>
</x-card>
