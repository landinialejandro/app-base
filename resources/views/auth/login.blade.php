<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>

<body>
    <h1>Login</h1>

    @if ($errors->any())
        <div>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/login">
        @csrf

        <label>Email</label>
        <input name="email" type="email" value="{{ old('email') }}" required autofocus>

        <label>Password</label>
        <input name="password" type="password" required>

        <label>
            <input type="checkbox" name="remember" value="1">
            Recordarme
        </label>

        <button type="submit">Entrar</button>
    </form>

    <p><a href="/register">Crear cuenta</a></p>
</body>

</html>