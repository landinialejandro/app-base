{{-- FILE: resources/views/landing/partials/cta.blade.php | V2 --}}
<section class="landing-section landing-section--cta">
    <div class="landing-container">
        <div class="landing-cta">
            <div class="landing-cta__content">
                <span class="landing-eyebrow">Empezá con una base superior</span>
                <h2>Tu empresa puede empezar a trabajar con una infraestructura pensada para destacar.</h2>
                <p>
                    Orden, continuidad, control, crecimiento y una experiencia premium en un mismo entorno.
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
