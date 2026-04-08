{{-- FILE: resources/views/landing/partials/footer.blade.php | V1 --}}
<footer class="landing-footer">
    <div class="landing-container landing-footer__inner">
        <div class="landing-footer__brand-block">
            <a href="{{ route('landing.home') }}" class="landing-footer__brand" aria-label="app-base">
                <span class="landing-footer__brand-icon" aria-hidden="true">
                    @include('svg.app-logo')
                </span>
            </a>

            <p>Base SaaS modular para gestión comercial y operativa.</p>
        </div>

        <div class="landing-footer__links">
            <a href="{{ route('landing.home') }}">Inicio</a>
            <a href="{{ route('landing.pricing') }}">Precios</a>

            @guest
                <a href="{{ route('login') }}">Ingresar</a>
                <a href="{{ route('public.signup-requests.create') }}">Solicitar acceso</a>
            @endguest
        </div>
    </div>
</footer>
