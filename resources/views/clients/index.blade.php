<h1>Clientes</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><a href="/dashboard">Volver al dashboard</a></p>
<p><a href="{{ route('clients.create') }}">Nuevo cliente</a></p>

@if (session('success'))
    <div style="border:1px solid green; padding:10px; margin-bottom:15px;">
        {{ session('success') }}
    </div>
@endif

<hr>

@if ($clients->isEmpty())
    <p>No hay clientes para este tenant.</p>
@else
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td><a href="{{ route('clients.show', $client) }}">{{ $client->name }}</a></td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->notes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif