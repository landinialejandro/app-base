<h1>Contactos</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><a href="{{ route('parties.create') }}">Nuevo Contacto</a></p>
<p><a href="{{ route('dashboard') }}">Volver al dashboard</a></p>

<hr>

@if (session('success'))
    <div style="border:1px solid green; padding:10px; margin-bottom:15px;">
        {{ session('success') }}
    </div>
@endif

@if ($parties->isEmpty())
    <p>No hay parties para este tenant.</p>
@else
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Activo</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($parties as $party)
                <tr>
                    <td>{{ $party->id }}</td>
                    <td>{{ $party->kind }}</td>
                    <td>
                        <a href="{{ route('parties.show', $party) }}">
                            {{ $party->name }}
                        </a>
                    </td>
                    <td>{{ $party->email }}</td>
                    <td>{{ $party->phone }}</td>
                    <td>{{ $party->is_active ? 'Sí' : 'No' }}</td>
                    <td>{{ $party->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif