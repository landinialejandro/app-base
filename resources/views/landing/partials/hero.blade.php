{{-- FILE: resources/views/landing/partials/hero.blade.php | V2 --}}
<section class="landing-hero">
    <div class="landing-container landing-hero__grid">
        <div class="landing-hero__content">
            <span class="landing-eyebrow">Infraestructura premium para empresas</span>

            <h1 class="landing-title">
                Tu empresa, elevada a otro nivel.
            </h1>

            <p class="landing-lead">
                app-base organiza, conecta y potencia la operación diaria en un entorno claro, sólido y preparado para
                acompañar el crecimiento de tu empresa.
            </p>

            <p class="landing-sublead">
                No es solo software. Es un sistema diseñado para trabajar con orden, continuidad, control y una
                experiencia
                superior desde el primer momento.
            </p>

            <div class="landing-hero__actions">
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
                        Solicitar una empresa
                    </a>

                    <a href="{{ route('landing.pricing') }}" class="landing-btn landing-btn--secondary">
                        Ver precios
                    </a>
                @endauth
            </div>

            <div class="landing-hero__notes">
                <div class="landing-mini-card">
                    <strong>Tu empresa, en un entorno propio</strong>
                    <span>Todo ocurre dentro de un espacio preparado, aislado y bajo tu control.</span>
                </div>

                <div class="landing-mini-card">
                    <strong>Listo para empezar y crecer</strong>
                    <span>Podés comenzar solo/a y sumar colaboradores cuando lo necesites.</span>
                </div>
            </div>
        </div>

        <div class="landing-hero__panel">
            <div class="landing-showcase">
                <div class="landing-showcase__top">
                    <span class="landing-dot"></span>
                    <span class="landing-dot"></span>
                    <span class="landing-dot"></span>
                </div>

                <div class="landing-showcase__body">
                    <div class="landing-showcase__block">
                        <span class="landing-showcase__label">Experiencia premium</span>
                        <strong>Todo preparado para trabajar mejor</strong>
                        <p>
                            Como entrar a un espacio propio, exclusivo y completamente equipado, donde cada parte
                            acompaña
                            tu forma de trabajar.
                        </p>
                    </div>

                    <div class="landing-showcase__stats">
                        <div class="landing-stat">
                            <span class="landing-stat__label">Base actual</span>
                            <strong class="landing-stat__value">9 módulos integrados</strong>
                        </div>

                        <div class="landing-stat">
                            <span class="landing-stat__label">Entorno de empresa</span>
                            <strong class="landing-stat__value">propio y aislado</strong>
                        </div>

                        <div class="landing-stat">
                            <span class="landing-stat__label">Evolución</span>
                            <strong class="landing-stat__value">en crecimiento constante</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
