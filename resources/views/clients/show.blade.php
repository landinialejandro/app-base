<h1>Detalle del cliente</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><a href="{{ route('clients.index') }}">Volver a clientes</a></p>
<p><a href="{{ route('clients.edit', $client) }}">Editar cliente</a></p>

<form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('¿Eliminar cliente?');">
    @csrf
    @method('DELETE')
    <button type="submit">Eliminar cliente</button>
</form>

<hr>

<p><strong>ID:</strong> {{ $client->id }}</p>
<p><strong>Nombre:</strong> {{ $client->name }}</p>
<p><strong>Email:</strong> {{ $client->email }}</p>
<p><strong>Teléfono:</strong> {{ $client->phone }}</p>
<p><strong>Notas:</strong> {{ $client->notes }}</p>