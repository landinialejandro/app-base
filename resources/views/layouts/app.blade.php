{{-- FILE: resources/views/layouts/app.blade.php --}}

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'app-base')</title>
    <link rel="stylesheet" href="{{ asset('css/app-base.css') }}">
</head>

<body>
    <div class="app-shell">
        @if (!($publicPage ?? false))

            @if (!($publicPage ?? false))
                <x-layout.navbar />
            @endif

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

        @if (!($publicPage ?? false))
            <footer class="app-footer">
                <div class="container app-footer-inner">
                    <span class="app-footer-brand">app-base</span>
                    <span class="app-footer-text">Sistema interno</span>
                </div>
            </footer>
        @endif
    </div>
    <script src="{{ asset('js/app-base.js') }}"></script>
</body>

</html>