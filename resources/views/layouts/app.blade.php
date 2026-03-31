{{-- FILE: resources/views/layouts/app.blade.php | V3 --}}

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'app-base')</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">

    {{-- Base visual global obligatoria del sistema --}}
    <link rel="stylesheet" href="{{ asset('css/app-base.css') }}">

    {{-- Patrones visuales reutilizables: index, show, tabs, badges, summaries, visuales --}}
    <link rel="stylesheet" href="{{ asset('css/app-patterns.css') }}">

    {{-- Estilos específicos de módulos complejos --}}
    <link rel="stylesheet" href="{{ asset('css/modules/appointments.css') }}">
</head>

<body>
    <div class="app-shell">
        @if (!($publicPage ?? false) && !(auth()->check() && auth()->user()->is_superadmin))
            <x-layout.navbar />
        @endif

        <main class="app-main">
            <div class="container">

                @if (session('success'))
                    <div class="alert alert-success" data-action="app-alert" data-alert-dismissible="true"
                        data-alert-autohide="true" data-alert-timeout="5000" role="status" aria-live="polite">
                        <div class="alert-content">
                            <div class="alert-body">
                                {{ session('success') }}
                            </div>

                            <button type="button" class="alert-dismiss" data-alert-dismiss aria-label="Cerrar alerta">
                                ×
                            </button>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error" data-action="app-alert" data-alert-dismissible="true"
                        data-alert-autohide="false" role="alert" aria-live="assertive">
                        <div class="alert-content">
                            <div class="alert-body">
                                {{ session('error') }}
                            </div>

                            <button type="button" class="alert-dismiss" data-alert-dismiss aria-label="Cerrar alerta">
                                ×
                            </button>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error" data-action="app-alert" data-alert-dismissible="true"
                        data-alert-autohide="false" role="alert" aria-live="assertive">
                        <div class="alert-content">
                            <div class="alert-body">
                                <div class="alert-title">Revisa los siguientes datos:</div>

                                <ul class="alert-list">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <button type="button" class="alert-dismiss" data-alert-dismiss aria-label="Cerrar alerta">
                                ×
                            </button>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        @if (!($publicPage ?? false) && !(auth()->check() && auth()->user()->is_superadmin))
            <x-layout.footer />
        @endif
    </div>

    <script src="{{ asset('js/app-base.js') }}"></script>
    @stack('scripts')
</body>

</html>
