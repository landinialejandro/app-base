<h1>Detalle del Contacto</h1>

<p>
    <a href="{{ route('parties.edit', $party) }}">
        Editar contacto
    </a>
</p>

<form method="POST" action="{{ route('parties.destroy', $party) }}" onsubmit="return confirm('¿Eliminar party?');">
    @csrf
    @method('DELETE')
    <button type="submit">Eliminar contacto</button>
</form>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>

<p><a href="{{ route('parties.index') }}">Volver a contactos</a></p>

<hr>

<p><strong>ID:</strong> {{ $party->id }}</p>
<p><strong>Tipo:</strong> {{ $party->kind }}</p>
<p><strong>Nombre:</strong> {{ $party->name }}</p>
<p><strong>Nombre visible:</strong> {{ $party->display_name }}</p>

<p><strong>Tipo documento:</strong> {{ $party->document_type }}</p>
<p><strong>Número documento:</strong> {{ $party->document_number }}</p>
<p><strong>CUIT / Tax ID:</strong> {{ $party->tax_id }}</p>

<p><strong>Email:</strong> {{ $party->email }}</p>
<p><strong>Teléfono:</strong> {{ $party->phone }}</p>
<p><strong>Dirección:</strong> {{ $party->address }}</p>

<p><strong>Notas:</strong> {{ $party->notes }}</p>
<p><strong>Activo:</strong> {{ $party->is_active ? 'Sí' : 'No' }}</p>

<p><strong>Creado:</strong> {{ $party->created_at }}</p>
<p><strong>Actualizado:</strong> {{ $party->updated_at }}</p>