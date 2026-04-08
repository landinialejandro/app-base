{{-- FILE: resources/views/landing/partials/cta.blade.php | V1 --}}
<section class="landing-section landing-section--cta">
    <div class="landing-container">
        <div class="landing-cta">
            <div class="landing-cta__content">
                <span class="landing-eyebrow">Empezá con una base sólida</span>
                <h2>Tu empresa puede empezar a trabajar con orden desde el primer momento.</h2>
                <p>
                    Solicitá acceso y avanzá con una estructura preparada para acompañar tu operación.
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
                        Ver precios
                    </a>
                @endauth
            </div>
        </div>
    </div>
</section>
