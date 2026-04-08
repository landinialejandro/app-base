{{-- FILE: resources/views/landing/partials/header.blade.php | V1 --}}
<header class="landing-header">
    <div class="landing-container landing-header__inner">
        <a href="{{ route('landing.home') }}" class="landing-brand" aria-label="app-base">
            <span class="landing-brand__icon" aria-hidden="true">
                @include('svg.app-logo')
            </span>
        </a>

        <nav class="landing-header__actions" aria-label="Accesos principales">
            @auth
                @if (auth()->user()->is_superadmin)
                    <a href="{{ route('admin.dashboard') }}" class="landing-btn landing-btn--ghost">
                        Ir a administración
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="landing-btn landing-btn--ghost">
                        Ir al dashboard
                    </a>
                @endif

                <a href="{{ route('landing.pricing') }}" class="landing-btn landing-btn--ghost">
                    Precios
                </a>

                <form method="POST" action="{{ route('logout') }}" class="landing-inline-form">
                    @csrf
                    <button type="submit" class="landing-btn landing-btn--secondary">
                        Cerrar sesión
                    </button>
                </form>
            @else
                <a href="{{ route('landing.pricing') }}" class="landing-btn landing-btn--ghost">
                    Precios
                </a>

                <a href="{{ route('login') }}" class="landing-btn landing-btn--ghost">
                    Ingresar
                </a>

                <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                    Solicitar acceso
                </a>
            @endauth
        </nav>
    </div>
</header>
