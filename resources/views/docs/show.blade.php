{{-- FILE: resources/views/docs/show.blade.php | V4 --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-patterns.css') }}">
</head>

<body style="min-height: 100vh; overflow-y: auto;">
    <div class="container" style="max-width: 1280px; margin: 0 auto; padding: 2rem 1rem 3rem;">
        <div style="margin-bottom: 1.5rem;">
            <nav aria-label="Breadcrumb" style="margin-bottom: .75rem;">
                <a href="{{ route('docs.index') }}">Documentación</a>
                <span style="color: var(--color-muted);"> / </span>
                <span>{{ $document->title }}</span>
            </nav>

            <h1 style="margin: 0 0 1rem 0;">{{ $document->title }}</h1>

            <x-list-filters-card :action="route('docs.show', ['slug' => $document->slug])" method="GET" :clear-url="route('docs.show', ['slug' => $document->slug])">
                <x-slot:primary>
                    <div class="form-group" style="margin: 0;">
                        <label for="doc-section-search" class="form-label">Buscar dentro del documento</label>
                        <input id="doc-section-search" type="text" name="section_q"
                            value="{{ $sectionSearch ?? '' }}" class="form-control" placeholder="Secciones o contenido">
                    </div>
                </x-slot:primary>
            </x-list-filters-card>
        </div>

        <div style="display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 1rem; align-items: start;">
            <div
                style="display: grid; gap: 1rem; position: sticky; top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto; padding-right: .25rem;">
                @include('docs.partials.document-nav', [
                    'documents' => $documents,
                    'currentDocument' => $document,
                ])

                <x-card>
                    <h2 style="margin-top: 0;">Secciones</h2>

                    <div style="max-height: 40vh; overflow-y: auto; padding-right: .25rem;">
                        <ul style="margin: 0; padding-left: 1rem;">
                            @foreach ($visibleSections as $section)
                                <li>
                                    <a href="#{{ $section->anchor }}">{{ $section->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-card>
            </div>

            <div style="display: grid; gap: 1rem; min-width: 0;">
                @forelse ($visibleSections as $section)
                    @include('docs.partials.section', ['section' => $section])
                @empty
                    <x-card>
                        <p style="margin: 0;">No se encontraron secciones para esa búsqueda.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </div>

    <script src="{{ asset('js/app-base.js') }}"></script>
</body>

</html>
