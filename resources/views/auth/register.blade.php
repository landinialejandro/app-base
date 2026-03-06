<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro</title>
</head>

<body>
    <h1>Registro</h1>

    @if ($errors->any())
        <div>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/register">
        @csrf

        <label>Nombre</label>
        <input name="name" type="text" value="{{ old('name') }}" required>

        <label>Email</label>
        <input name="email" type="email" value="{{ old('email') }}" required>

        <label>Password</label>
        <input name="password" type="password" required>

        <label>Confirmar password</label>
        <input name="password_confirmation" type="password" required>

        <button type="submit">Crear cuenta</button>
    </form>

    <p><a href="/login">Volver al login</a></p>
</body>

</html>