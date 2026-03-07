<h1>Nuevo contacto</h1>

<p><a href="{{ route('parties.index') }}">Volver a parties</a></p>

@if ($errors->any())
    <div style="color: red;">
        <p><strong>Hay errores en el formulario:</strong></p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('parties.store') }}">
    @csrf
    @include('parties._form', ['party' => null])
</form>