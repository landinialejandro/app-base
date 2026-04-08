{{-- FILE: resources/views/landing/pricing.blade.php | V1 --}}
@extends('layouts.landing')

@section('title', 'Precios | app-base')

@section('body')
    @include('landing.partials.header')

    <main>
        <section class="landing-simple-hero">
            <div class="landing-container">
                <span class="landing-eyebrow">Precios</span>
                <h1 class="landing-title landing-title--md">
                    Planes y alcance de implementación
                </h1>
                <p class="landing-lead landing-lead--narrow">
                    Esta página queda preparada para presentar planes, condiciones comerciales y alcance de puesta en
                    marcha sin mezclar esa información con la landing principal.
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
                    <span class="landing-eyebrow">Página preparada</span>
                    <h2>Acá podés definir tu estrategia comercial sin tocar la home.</h2>
                    <p>
                        Podés presentar planes fijos, implementación a medida, cotización por rubro, versión inicial,
                        servicios adicionales o una combinación de esas opciones.
                    </p>
                </div>

                <div class="landing-grid landing-grid--3">
                    <article class="landing-card">
                        <h3>Plan base</h3>
                        <p>Ideal para presentar un punto de entrada claro con acceso inicial y estructura operativa lista.
                        </p>
                    </article>

                    <article class="landing-card">
                        <h3>Escala por necesidad</h3>
                        <p>Podés diferenciar por cantidad de usuarios, módulos activos, soporte o complejidad operativa.</p>
                    </article>

                    <article class="landing-card">
                        <h3>Implementación consultiva</h3>
                        <p>También puede usarse como página comercial para presupuestos y acompañamiento personalizado.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-container">
                <div class="landing-cta">
                    <div class="landing-cta__content">
                        <span class="landing-eyebrow">Siguiente paso</span>
                        <h2>Cuando definas los precios, esta página ya queda lista para recibirlos.</h2>
                        <p>
                            Mientras tanto, el flujo público puede seguir apuntando a la solicitud de acceso.
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
