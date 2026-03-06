<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Seleccionar empresa</title>
</head>
<body>
  <h1>Seleccionar empresa</h1>

  @if($tenants->isEmpty())
    <p>No tenés empresas asignadas.</p>
  @else
    <ul>
      @foreach($tenants as $tenant)
        <li>
          <form method="POST" action="{{ route('tenants.select.store', $tenant) }}">
            @csrf
            <button type="submit">
              {{ $tenant->name }} ({{ $tenant->slug }})
            </button>
          </form>
        </li>
      @endforeach
    </ul>
  @endif
</body>
</html>