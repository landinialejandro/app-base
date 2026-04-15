{{-- FILE: resources/views/landing/partials/hero.blade.php | V3 --}}
<section class="landing-hero">
    <div class="landing-container landing-hero__grid">
        <div class="landing-hero__content">
            <span class="landing-eyebrow">Plataforma de gestión y crecimiento para empresas</span>

            <h1 class="landing-title">
                Ordená tu operación en un entorno propio y prepará tu empresa para crecer.
            </h1>

            <p class="landing-lead">
                app-base no vende módulos sueltos. Te da una base clara, sólida y conectada para trabajar con más
                continuidad, control y coherencia.
            </p>

            <p class="landing-sublead">
                Es una infraestructura pensada para empresas que necesitan dejar atrás la dispersión, sostener mejor su
                trabajo diario y crecer sobre una base profesional.
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
                        Solicitar acceso
                    </a>

                    <a href="{{ route('landing.pricing') }}" class="landing-btn landing-btn--secondary">
                        Ver planes
                    </a>
                @endauth
            </div>

            <div class="landing-hero__notes">
                <div class="landing-mini-card">
                    <strong>Tu empresa trabaja en un entorno propio</strong>
                    <span>Información organizada, acceso cuidado y una base clara para operar con confianza.</span>
                </div>

                <div class="landing-mini-card">
                    <strong>Inventario ya es una capacidad activa</strong>
                    <span>Se encuentra en fase beta en operación y forma parte del valor actual del sistema.</span>
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
                        <span class="landing-showcase__label">Base operativa conectada</span>
                        <strong>Todo preparado para trabajar con más orden y continuidad</strong>
                        <p>
                            Agenda, trabajo, órdenes, documentos, activos, productos y seguimiento dentro de un mismo
                            entorno, sin depender de parches dispersos.
                        </p>
                    </div>

                    <div class="landing-showcase__stats">
                        <div class="landing-stat">
                            <span class="landing-stat__label">Entorno</span>
                            <strong class="landing-stat__value">propio y preparado para la empresa</strong>
                        </div>

                        <div class="landing-stat">
                            <span class="landing-stat__label">Capacidad actual</span>
                            <strong class="landing-stat__value">inventario beta operativa</strong>
                        </div>

                        <div class="landing-stat">
                            <span class="landing-stat__label">Evolución</span>
                            <strong class="landing-stat__value">IA como capa de valor sobre una base sólida</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
