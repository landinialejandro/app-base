<h1>Editar cliente</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><a href="{{ route('clients.show', $client) }}">Volver al cliente</a></p>

<hr>

@if ($errors->any())
    <div style="border:1px solid red; padding:10px; margin-bottom:15px;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('clients.update', $client) }}">
    @csrf
    @method('PUT')

    <div style="margin-bottom: 12px;">
        <label>Nombre</label><br>
        <input type="text" name="name" value="{{ old('name', $client->name) }}" required style="width: 400px;">
    </div>

    <div style="margin-bottom: 12px;">
        <label>Email</label><br>
        <input type="email" name="email" value="{{ old('email', $client->email) }}" style="width: 400px;">
    </div>

    <div style="margin-bottom: 12px;">
        <label>Teléfono</label><br>
        <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" style="width: 400px;">
    </div>

    <div style="margin-bottom: 12px;">
        <label>Notas</label><br>
        <textarea name="notes" rows="5" style="width: 400px;">{{ old('notes', $client->notes) }}</textarea>
    </div>

    <button type="submit">Actualizar cliente</button>
</form>