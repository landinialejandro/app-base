{{-- FILE: resources/views/landing/pricing.blade.php | V2 --}}
@extends('layouts.landing')

@section('title', 'Precios | app-base')

@section('body')
    @include('landing.partials.header')

    <main>
        <section class="landing-simple-hero">
            <div class="landing-container">
                <span class="landing-eyebrow">Planes y acceso</span>
                <h1 class="landing-title landing-title--md">
                    Elegí la forma de incorporar app-base a tu empresa
                </h1>
                <p class="landing-lead landing-lead--narrow">
                    Una infraestructura premium para organizar, conectar y elevar tu operación diaria. Desde una base sólida
                    lista para usar hasta una evolución más amplia con asistencia inteligente y nuevas capacidades.
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
                    <span class="landing-eyebrow">Una base que se adapta</span>
                    <h2>No se trata solo de precio. Se trata del nivel de infraestructura que querés para tu empresa.</h2>
                    <p>
                        app-base puede acompañarte desde una etapa inicial hasta una operación más amplia, manteniendo una
                        experiencia clara, ordenada y preparada para crecer.
                    </p>
                </div>

                <div class="landing-grid landing-grid--3">
                    <article class="landing-card">
                        <h3>Inicio sólido</h3>
                        <p>
                            Ideal para empezar con una base operativa clara, lista para trabajar desde el primer momento y
                            sin
                            fricción innecesaria.
                        </p>
                    </article>

                    <article class="landing-card">
                        <h3>Crecimiento organizado</h3>
                        <p>
                            Pensado para empresas que necesitan más estructura, más continuidad y una base capaz de
                            acompañar
                            el crecimiento del equipo y la operación.
                        </p>
                    </article>

                    <article class="landing-card">
                        <h3>Evolución premium</h3>
                        <p>
                            Preparado para incorporar nuevas capacidades, como asistencia inteligente y marketplace
                            habilitable, dentro del mismo entorno de trabajo.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-container">
                <div class="landing-section__header">
                    <span class="landing-eyebrow">Tres formas de avanzar</span>
                    <h2>Una propuesta simple para presentar el sistema con claridad comercial.</h2>
                    <p>
                        Podés ajustar nombres, condiciones y precios después. Lo importante ahora es dejar una estructura
                        premium, clara y escalable.
                    </p>
                </div>

                <div class="landing-grid landing-grid--3">
                    <article class="landing-pricing-card">
                        <div class="landing-pricing-card__header">
                            <span class="landing-pricing-card__tag">Base</span>
                            <h3>Esencial</h3>
                            <p>Para empezar con una operación ordenada sobre una base sólida.</p>
                        </div>

                        <div class="landing-pricing-card__price">
                            <strong>A definir</strong>
                            <span>mensual o por implementación</span>
                        </div>

                        <ul class="landing-pricing-list">
                            <li>Entorno propio para la empresa</li>
                            <li>Base operativa lista para usar</li>
                            <li>Módulos principales integrados</li>
                            <li>Ideal para trabajo individual o equipos pequeños</li>
                        </ul>

                        <div class="landing-pricing-card__actions">
                            <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                                Solicitar acceso
                            </a>
                        </div>
                    </article>

                    <article class="landing-pricing-card landing-pricing-card--featured">
                        <div class="landing-pricing-card__header">
                            <span class="landing-pricing-card__tag">Recomendado</span>
                            <h3>Profesional</h3>
                            <p>Para empresas que quieren trabajar con más amplitud, continuidad y control.</p>
                        </div>

                        <div class="landing-pricing-card__price">
                            <strong>A definir</strong>
                            <span>mensual o por implementación</span>
                        </div>

                        <ul class="landing-pricing-list">
                            <li>Base operativa premium</li>
                            <li>Escalable para más usuarios y más organización</li>
                            <li>Preparado para crecimiento modular</li>
                            <li>Ideal para estructuras más exigentes</li>
                        </ul>

                        <div class="landing-pricing-card__actions">
                            <a href="{{ route('public.signup-requests.create') }}" class="landing-btn landing-btn--primary">
                                Solicitar acceso
                            </a>
                        </div>
                    </article>

                    <article class="landing-pricing-card">
                        <div class="landing-pricing-card__header">
                            <span class="landing-pricing-card__tag">Expansión</span>
                            <h3>Evolution</h3>
                            <p>Para acompañar una visión más amplia del negocio y del crecimiento del sistema.</p>
                        </div>

                        <div class="landing-pricing-card__price">
                            <strong>A medida</strong>
                            <span>según alcance</span>
                        </div>

                        <ul class="landing-pricing-list">
                            <li>Hoja de ruta de crecimiento sobre la base actual</li>
                            <li>Agente de IA administrativo</li>
                            <li>Marketplace habilitable</li>
                            <li>Implementación consultiva y evolución progresiva</li>
                        </ul>

                        <div class="landing-pricing-card__actions">
                            <a href="{{ route('public.signup-requests.create') }}"
                                class="landing-btn landing-btn--secondary">
                                Consultar
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--dark">
            <div class="landing-container">
                <div class="landing-section__header landing-section__header--light">
                    <span class="landing-eyebrow">Qué incluye la propuesta</span>
                    <h2>Una base actual fuerte, preparada para ampliar su alcance.</h2>
                    <p>
                        Hoy ya contás con una estructura operativa integrada. Y al mismo tiempo, la plataforma está pensada
                        para seguir sumando valor sin perder coherencia.
                    </p>
                </div>

                <div class="landing-grid landing-grid--3">
                    <article class="landing-module-card">
                        <h3>Base actual</h3>
                        <p>Contactos, agenda, tareas, órdenes, documentos, activos, productos, proyectos y adjuntos.</p>
                    </article>

                    <article class="landing-module-card">
                        <h3>Asistencia inteligente</h3>
                        <p>Preparada para incorporar un agente de IA administrativo dentro del mismo entorno de trabajo.</p>
                    </article>

                    <article class="landing-module-card">
                        <h3>Marketplace habilitable</h3>
                        <p>Posibilidad de abrir una nueva capa comercial sin salir de la plataforma.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-container">
                <div class="landing-section__header">
                    <span class="landing-eyebrow">Una aclaración importante</span>
                    <h2>Tu empresa trabaja en un entorno propio.</h2>
                    <p>
                        No compartís información ni dependés de otras empresas. Todo sucede dentro de un espacio separado,
                        preparado y bajo tu control.
                    </p>
                </div>

                <div class="landing-grid landing-grid--2">
                    <article class="landing-feature">
                        <h3>Claridad desde el inicio</h3>
                        <p>Entrás a una base preparada para trabajar con orden, sin configuraciones innecesarias.</p>
                    </article>

                    <article class="landing-feature">
                        <h3>Una experiencia premium real</h3>
                        <p>La propuesta apunta a que tu empresa funcione dentro de un entorno cuidado, sólido y profesional.
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
                        <h2>Podés empezar con una base sólida hoy y ampliar el alcance mañana.</h2>
                        <p>
                            Solicitá acceso y evaluemos cuál es la mejor forma de incorporar app-base a tu empresa.
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
