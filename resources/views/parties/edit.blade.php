<h1>Editar contacto</h1>

<p><a href="{{ route('parties.show', $party) }}">Volver a la party</a></p>

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

<form method="POST" action="{{ route('parties.update', $party) }}">
    @csrf
    @method('PUT')
    @include('parties._form', ['party' => $party])
</form>