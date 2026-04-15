{{-- FILE: resources/views/landing/partials/cta.blade.php | V3 --}}
<section class="landing-section landing-section--cta">
    <div class="landing-container">
        <div class="landing-cta">
            <div class="landing-cta__content">
                <span class="landing-eyebrow">Siguiente paso</span>

                <h2>
                    Empezá con una base sólida hoy y ampliá el alcance cuando tu empresa lo necesite.
                </h2>

                <p>
                    app-base está pensado para empresas que necesitan más orden, más continuidad y una estructura clara
                    para crecer sin improvisación. Podemos ayudarte a evaluar si conviene comenzar con un plan estándar
                    o avanzar con una implementación particular.
                </p>
            </div>

            <div class="landing-cta__actions">
                @auth
                    @if (auth()->user()->is_superadmin)
                        <a href="{{ route('admin.dashboard') }}" class="landing-btn landing-btn--primary">
                            Ir a administración
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="landing-btn landing-btn--primary">
                            Ir al dashboard
                        </a>
                    @endif
                @else
                    <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                        Solicitar acceso
                    </a>

                    <a href="{{ route('landing.pricing') }}" class="landing-btn landing-btn--secondary">
                        Ver planes
                    </a>
                @endauth
            </div>
        </div>
    </div>
</section>
