{{-- FILE: resources/views/parties/partials/table.blade.php | V1 --}}

@php
    use App\Support\Catalogs\PartyCatalog;
    use App\Support\Navigation\NavigationTrail;

    $parties = $parties ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay contactos para mostrar.';
    $trailQuery = $trailQuery ?? [];
    $containerTrail = NavigationTrail::decode($trailQuery['trail'] ?? null);
@endphp

@if ($parties->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Activo</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($parties as $party)
                    @php
                        $rowTrail = NavigationTrail::appendOrCollapse(
                            $containerTrail,
                            NavigationTrail::makeNode(
                                'parties.show',
                                $party->id,
                                $party->name ?: 'Contacto #' . $party->id,
                                route('parties.show', ['party' => $party]),
                            ),
                        );

                        if (empty($rowTrail)) {
                            $rowTrail = NavigationTrail::base([
                                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                                NavigationTrail::makeNode('parties.index', null, 'Contactos', route('parties.index')),
                                NavigationTrail::makeNode(
                                    'parties.show',
                                    $party->id,
                                    $party->name ?: 'Contacto #' . $party->id,
                                    route('parties.show', ['party' => $party]),
                                ),
                            ]);
                        }

                        $rowTrailQuery = NavigationTrail::toQuery($rowTrail);
                    @endphp

                    <tr>
                        <td>{{ $party->id }}</td>
                        <td>
                            <a href="{{ route('parties.show', ['party' => $party] + $rowTrailQuery) }}">
                                {{ $party->name }}
                            </a>
                        </td>
                        <td>{{ $party->email ?? '—' }}</td>
                        <td>{{ $party->phone ?? '—' }}</td>
                        <td>
                            <span
                                class="status-badge {{ $party->is_active ? 'status-badge--done' : 'status-badge--cancelled' }}">
                                {{ $party->is_active ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td>{{ $party->kind ? PartyCatalog::label($party->kind) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif
