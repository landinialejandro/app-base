<h1>Nuevo proyecto</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><a href="/projects">Volver a proyectos</a></p>

<hr>

@if ($errors->any())
    <div style="border:1px solid red; padding:10px; margin-bottom:15px;">
        <strong>Hay errores en el formulario:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('projects.store') }}">
    @csrf

    <div style="margin-bottom: 12px;">
        <label for="name">Nombre</label><br>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name') }}"
            required
            style="width: 400px;"
        >
    </div>

    <div style="margin-bottom: 12px;">
        <label for="description">Descripción</label><br>
        <textarea
            id="description"
            name="description"
            rows="5"
            style="width: 400px;"
        >{{ old('description') }}</textarea>
    </div>

    <button type="submit">Crear proyecto</button>
</form>