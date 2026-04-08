{{-- FILE: resources/views/landing/partials/hero.blade.php | V1 --}}
<section class="landing-hero">
    <div class="landing-container landing-hero__grid">
        <div class="landing-hero__content">
            <span class="landing-eyebrow">Base operativa modular para empresas</span>

            <h1 class="landing-title">
                Ordená tu operación en un solo sistema, sin sumar complejidad.
            </h1>

            <p class="landing-lead">
                app-base conecta agenda, contactos, órdenes, documentos, activos, tareas y seguimiento operativo en un
                entorno claro, sólido y preparado para crecer con tu forma de trabajar.
            </p>

            <p class="landing-sublead">
                Pensado para acompañar talleres, servicios, comercios, consultorios y otros entornos donde la actividad
                diaria necesita orden, continuidad y control.
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
                    <strong>Para trabajar solo/a</strong>
                    <span>No necesitás un equipo para empezar.</span>
                </div>

                <div class="landing-mini-card">
                    <strong>Para crecer con orden</strong>
                    <span>Podés sumar colaboradores cuando lo necesites.</span>
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
                        <span class="landing-showcase__label">Operación conectada</span>
                        <strong>Agenda → Orden → Documento</strong>
                        <p>Un flujo continuo para que el trabajo no se corte entre módulos.</p>
                    </div>

                    <div class="landing-showcase__stats">
                        <div class="landing-stat">
                            <span class="landing-stat__label">Base modular</span>
                            <strong class="landing-stat__value">9 módulos</strong>
                        </div>

                        <div class="landing-stat">
                            <span class="landing-stat__label">Acceso por empresa</span>
                            <strong class="landing-stat__value">multi-tenant</strong>
                        </div>

                        <div class="landing-stat">
                            <span class="landing-stat__label">Organización</span>
                            <strong class="landing-stat__value">clara y escalable</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
