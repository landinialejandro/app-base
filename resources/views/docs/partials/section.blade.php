{{-- FILE: resources/views/docs/partials/section.blade.php | V5 --}}

@php
    $collapsed = false;
    $editorId = 'doc-section-editor-' . $section->anchor;
    $canEditSection = ($docsEditorEnabled ?? false) && $section->name !== 'METADATOS';
@endphp

<x-card :collapsible="true" :collapsed="$collapsed" id="{{ $section->anchor }}">
    <x-slot:header>
        <div>
            <h2 class="card-title">{{ $section->name }}</h2>
        </div>
    </x-slot:header>

    <x-slot:toolbox>
        <div style="display: flex; align-items: center; gap: .35rem;">
            @if ($canEditSection)
                <button type="button" class="card-tool" data-action="app-toggle-details"
                    data-toggle-target="#{{ $editorId }}" aria-label="Editar sección {{ $section->name }}"
                    title="Editar sección {{ $section->name }}">
                    <span aria-hidden="true">✎</span>
                </button>
            @endif

            <button type="button" class="card-tool" data-action="app-card-toggle"
                aria-label="Expandir o contraer sección {{ $section->name }}"
                title="Expandir o contraer sección {{ $section->name }}"
                aria-expanded="{{ $collapsed ? 'false' : 'true' }}">
                <span class="icon-expand">
                    <x-icons.chevron-down />
                </span>
            </button>
        </div>
    </x-slot:toolbox>

    @if ($canEditSection)
        <div id="{{ $editorId }}" hidden style="margin-bottom: 1rem;">
            <form method="POST"
                action="{{ route('docs.sections.update', ['slug' => $document->slug, 'section' => $section->anchor]) }}"
                class="form">
                @csrf
                @method('PUT')

                <input type="hidden" name="section_q" value="{{ $sectionSearch ?? '' }}">

                <div class="form-group">
                    <label for="section_body_{{ $section->anchor }}" class="form-label">Contenido de la sección</label>
                    <textarea id="section_body_{{ $section->anchor }}" name="section_body" class="form-control" rows="14">{{ old('section_body', $section->rawBody) }}</textarea>

                    @error('section_body')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions" style="display: flex; gap: .5rem; align-items: center;">
                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>

                    <button type="button" class="btn btn-secondary btn-sm" data-action="app-toggle-details"
                        data-toggle-target="#{{ $editorId }}">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="content">
        {!! $section->html !!}
    </div>
</x-card>
