{{-- FILE: resources/views/docs/index.blade.php | V3 --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Documentación técnica</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-patterns.css') }}">
</head>

<body style="min-height: 100vh; overflow-y: auto;">
    <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 2rem 1rem 3rem;">
        <div style="margin-bottom: 1.5rem;">
            <p style="margin: 0 0 .5rem 0; color: var(--color-muted);">app-base</p>
            <h1 style="margin: 0;">Documentación técnica</h1>
        </div>

        <x-list-filters-card :action="route('docs.index')" method="GET" :clear-url="route('docs.index')">
            <x-slot:primary>
                <div class="form-group" style="margin: 0;">
                    <label for="docs-search" class="form-label">Buscar</label>
                    <input id="docs-search" type="text" name="q" value="{{ $search ?? '' }}"
                        class="form-control" placeholder="Título, slug o contenido">
                </div>
            </x-slot:primary>
        </x-list-filters-card>

        <x-card>
            @if (empty($documents))
                <p style="margin: 0;">No se encontraron documentos técnicos activos.</p>
            @else
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Slug</th>
                                <th>Versión</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $document)
                                <tr>
                                    <td>
                                        <a
                                            href="{{ route('docs.show', ['slug' => $document->slug] + (filled($search ?? null) ? ['q' => $search] : [])) }}">
                                            {{ $document->title }}
                                        </a>
                                    </td>
                                    <td><code>{{ $document->slug }}</code></td>
                                    <td>{{ $document->version }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>

    <script src="{{ asset('js/app-base.js') }}"></script>
</body>

</html>
