<h1>Nuevo cliente</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><a href="{{ route('clients.index') }}">Volver a clientes</a></p>

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

<form method="POST" action="{{ route('clients.store') }}">
    @csrf

    <div style="margin-bottom: 12px;">
        <label>Nombre</label><br>
        <input type="text" name="name" value="{{ old('name') }}" required style="width: 400px;">
    </div>

    <div style="margin-bottom: 12px;">
        <label>Email</label><br>
        <input type="email" name="email" value="{{ old('email') }}" style="width: 400px;">
    </div>

    <div style="margin-bottom: 12px;">
        <label>Teléfono</label><br>
        <input type="text" name="phone" value="{{ old('phone') }}" style="width: 400px;">
    </div>

    <div style="margin-bottom: 12px;">
        <label>Notas</label><br>
        <textarea name="notes" rows="5" style="width: 400px;">{{ old('notes') }}</textarea>
    </div>

    <button type="submit">Crear cliente</button>
</form>