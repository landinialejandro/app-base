{{-- FILE: resources/views/landing/pricing.blade.php | V3 --}}
@extends('layouts.landing')

@section('title', 'Planes | app-base')

@section('body')
    @include('landing.partials.header')

    <main>
        <section class="landing-simple-hero">
            <div class="landing-container">
                <span class="landing-eyebrow">Planes y modalidades</span>
                <h1 class="landing-title landing-title--md">
                    Elegí la forma de incorporar app-base a tu empresa
                </h1>
                <p class="landing-lead landing-lead--narrow">
                    app-base no se presenta como una suma de módulos sueltos. Se presenta como una base de trabajo clara,
                    sólida y preparada para ordenar la operación, sostener continuidad y acompañar crecimiento real.
                </p>

                <div class="landing-hero__actions">
                    <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                        Solicitar acceso
                    </a>

                    <a href="{{ route('landing.home') }}" class="landing-btn landing-btn--secondary">
                        Volver al inicio
                    </a>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--soft">
            <div class="landing-container">
                <div class="landing-section__header">
                    <span class="landing-eyebrow">Cómo se organiza la propuesta</span>
                    <h2>Una estructura comercial simple, clara y preparada para crecer con la empresa.</h2>
                    <p>
                        Los planes estándar se ordenan por capacidad operativa, cantidad de usuarios y nivel de servicio.
                        Enterprise se trabaja como una modalidad particular para necesidades de mayor exclusividad,
                        personalización o acompañamiento.
                    </p>
                </div>

                <div class="landing-grid landing-grid--3">
                    <article class="landing-card">
                        <h3>Base para empezar bien</h3>
                        <p>
                            Starter permite comenzar con una estructura clara, en un entorno propio y sin cargar a la
                            empresa con complejidad innecesaria.
                        </p>
                    </article>

                    <article class="landing-card">
                        <h3>Crecimiento con más continuidad</h3>
                        <p>
                            Growth y Scale acompañan empresas que necesitan más equipo, más formalización y una operación
                            cada vez más madura.
                        </p>
                    </article>

                    <article class="landing-card">
                        <h3>Implementación particular</h3>
                        <p>
                            Enterprise no se plantea como un simple upgrade. Es una modalidad consultiva para organizaciones
                            que requieren una solución más exclusiva o personalizada.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-container">
                <div class="landing-section__header">
                    <span class="landing-eyebrow">Planes vigentes</span>
                    <h2>Cuatro formas de avanzar según el nivel de operación que necesita tu empresa.</h2>
                    <p>
                        La lectura principal no pasa por pagar módulos por separado. Pasa por contar con una base operativa
                        acorde al tamaño del equipo, al nivel de formalización y a la exigencia del trabajo diario.
                    </p>
                </div>

                <div class="landing-grid landing-grid--3">
                    <article class="landing-pricing-card">
                        <div class="landing-pricing-card__header">
                            <span class="landing-pricing-card__tag">Starter</span>
                            <h3>Starter</h3>
                            <p>Para empresas pequeñas o en etapa inicial que necesitan empezar con orden y claridad.</p>
                        </div>

                        <div class="landing-pricing-card__price">
                            <strong>USD 26</strong>
                            <span>por mes · hasta 5 usuarios</span>
                        </div>

                        <ul class="landing-pricing-list">
                            <li>Entorno propio para la empresa</li>
                            <li>Base operativa lista para trabajar</li>
                            <li>Agenda, tareas, órdenes, activos, productos y seguimiento</li>
                            <li>Ideal para comenzar con una estructura sólida</li>
                            <li>Documents no incluido</li>
                        </ul>

                        <div class="landing-pricing-card__actions">
                            <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                                Empezar con Starter
                            </a>
                        </div>
                    </article>

                    <article class="landing-pricing-card landing-pricing-card--featured">
                        <div class="landing-pricing-card__header">
                            <span class="landing-pricing-card__tag">Growth</span>
                            <h3>Growth</h3>
                            <p>Para empresas en crecimiento que necesitan más estructura, más continuidad y más
                                formalización.</p>
                        </div>

                        <div class="landing-pricing-card__price">
                            <strong>USD 50</strong>
                            <span>por mes · hasta 10 usuarios</span>
                        </div>

                        <ul class="landing-pricing-list">
                            <li>Documents incluido</li>
                            <li>Más capacidad para trabajo en equipo</li>
                            <li>Mayor continuidad operativa</li>
                            <li>Base preparada para una operación más madura</li>
                            <li>Ideal para crecimiento organizado</li>
                        </ul>

                        <div class="landing-pricing-card__actions">
                            <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                                Elegir Growth
                            </a>
                        </div>
                    </article>

                    <article class="landing-pricing-card">
                        <div class="landing-pricing-card__header">
                            <span class="landing-pricing-card__tag">Scale</span>
                            <h3>Scale</h3>
                            <p>Para operaciones intensivas o equipos consolidados que necesitan más amplitud y más
                                proyección.</p>
                        </div>

                        <div class="landing-pricing-card__price">
                            <strong>USD 99</strong>
                            <span>por mes · hasta 20 usuarios</span>
                        </div>

                        <ul class="landing-pricing-list">
                            <li>Documents incluido</li>
                            <li>Mayor capacidad operativa para equipos amplios</li>
                            <li>Base premium para trabajo más intenso</li>
                            <li>Más proyección para sostener crecimiento real</li>
                            <li>Preparado para una operación más exigente</li>
                        </ul>

                        <div class="landing-pricing-card__actions">
                            <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                                Elegir Scale
                            </a>
                        </div>
                    </article>
                </div>

                <div style="margin-top: 1.35rem;">
                    <article class="landing-cta">
                        <div class="landing-cta__content">
                            <span class="landing-eyebrow">Enterprise</span>
                            <h2>Una modalidad particular para empresas que necesitan una implementación más exclusiva.</h2>
                            <p>
                                Enterprise se resuelve de forma consultiva. Puede incluir instancia dedicada, branding
                                propio, dominio propio, migración de datos, integraciones, ajustes funcionales, soporte
                                preferente y una implementación más acompañada.
                            </p>
                        </div>

                        <div class="landing-cta__actions">
                            <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                                Consultar Enterprise
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--dark">
            <div class="landing-container">
                <div class="landing-section__header landing-section__header--light">
                    <span class="landing-eyebrow landing-eyebrow--light">Capas de valor</span>
                    <h2>La propuesta crece con la empresa sin perder claridad comercial.</h2>
                    <p>
                        app-base combina base operativa actual, capacidades activas en evolución y una proyección clara de
                        crecimiento dentro del mismo entorno de trabajo.
                    </p>
                </div>

                <div class="landing-grid landing-grid--3">
                    <article class="landing-module-card">
                        <h3>Documents como capa avanzada</h3>
                        <p>
                            Documents se habilita desde Growth porque agrega una capa más exigente de formalización,
                            mantenimiento y soporte sobre la operación.
                        </p>
                    </article>

                    <article class="landing-module-card">
                        <h3>Inventario como valor actual</h3>
                        <p>
                            Inventario ya debe leerse como capacidad real del producto. Se encuentra en fase beta en
                            operación y forma parte del valor actual del sistema.
                        </p>
                    </article>

                    <article class="landing-module-card">
                        <h3>IA como evolución</h3>
                        <p>
                            La asistencia inteligente se incorpora como capa evolutiva de valor para aumentar productividad,
                            claridad y capacidad operativa dentro del mismo entorno.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-container">
                <div class="landing-section__header">
                    <span class="landing-eyebrow">Usuarios adicionales y crecimiento</span>
                    <h2>La empresa puede ampliar capacidad sin romper la lógica natural de los planes.</h2>
                    <p>
                        Los planes estándar admiten usuarios adicionales cuando hace falta absorber una necesidad intermedia
                        de crecimiento, sin perder una estructura simple y entendible.
                    </p>
                </div>

                <div class="landing-grid landing-grid--2">
                    <article class="landing-feature">
                        <h3>Usuarios adicionales</h3>
                        <p>
                            Valor vigente: USD 5,5 por usuario adicional por mes.
                        </p>
                    </article>

                    <article class="landing-feature">
                        <h3>Upgrade natural</h3>
                        <p>
                            Cuando el crecimiento lo justifique, la propuesta comercial puede sugerir migración al plan
                            superior para mantener una relación clara entre capacidad operativa y valor entregado.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--soft">
            <div class="landing-container">
                <div class="landing-section__header">
                    <span class="landing-eyebrow">Confianza comercial</span>
                    <h2>La relación con el cliente debe sostenerse por valor, no por dependencia forzada.</h2>
                    <p>
                        app-base trabaja sobre una idea simple: el cliente debe sentirse seguro trabajando en un entorno
                        propio, con control claro y con una portabilidad razonable de su información principal.
                    </p>
                </div>

                <div class="landing-grid landing-grid--2">
                    <article class="landing-card">
                        <h3>Tus datos son tuyos</h3>
                        <p>
                            La información principal del negocio debe poder exportarse mediante un proceso razonable,
                            seguro y útil, sin prometer atajos irresponsables ni dependencia artificial.
                        </p>
                    </article>

                    <article class="landing-card">
                        <h3>Entorno propio para crecer</h3>
                        <p>
                            La empresa trabaja dentro de un espacio claro, sólido y preparado para crecer con coherencia,
                            sin tener que rehacer todo cada vez que aumenta la exigencia operativa.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--cta">
            <div class="landing-container">
                <div class="landing-cta">
                    <div class="landing-cta__content">
                        <span class="landing-eyebrow">Siguiente paso</span>
                        <h2>Empezá con una base sólida hoy y ampliá el alcance cuando tu empresa lo necesite.</h2>
                        <p>
                            Podemos ayudarte a evaluar si conviene comenzar con un plan estándar o avanzar con una
                            implementación particular.
                        </p>
                    </div>

                    <div class="landing-cta__actions">
                        <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                            Solicitar acceso
                        </a>

                        <a href="{{ route('landing.home') }}" class="landing-btn landing-btn--secondary">
                            Volver al inicio
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('landing.partials.footer')
@endsection
