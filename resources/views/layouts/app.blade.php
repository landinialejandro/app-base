<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'app-base')</title>
    <link rel="stylesheet" href="{{ asset('css/app-base.css') }}">
</head>

<body>
    @if(!($publicPage ?? false))

        <header class="app-header">
            <div class="container app-header-inner">

                <div class="app-brand">
                    <a href="{{ url('/') }}">
                        app-base
                    </a>
                </div>

                <nav class="app-nav">
                    @auth
                        <a class="app-nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        <a class="app-nav-link" href="{{ route('projects.index') }}">Projects</a>
                        <a class="app-nav-link" href="{{ route('parties.index') }}">Contacts</a>
                    @endauth
                </nav>

                <div class="app-header-actions">

                    @auth
                        @if(app()->bound('tenant'))

                            <div class="app-company">
                                <span class="app-company-label">Empresa</span>
                                <span class="app-company-name">
                                    {{ app('tenant')->name }}
                                </span>
                            </div>

                            @if(auth()->user()->tenants->count() > 1)
                                <a class="btn btn-secondary" href="{{ route('tenants.select') }}">
                                    Cambiar
                                </a>
                            @endif
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-link" type="submit">
                                Cerrar sesión
                            </button>
                        </form>
                    @endauth

                </div>

            </div>
        </header>

    @endif

    <main class="app-main">
        <div class="container">

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>

</html>