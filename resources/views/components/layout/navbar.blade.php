<header class="app-header">
    <div class="container app-header-inner">

        <div class="app-brand">
            <a href="{{ url('/') }}">
                app-base
            </a>
        </div>

        <nav class="app-nav">
            @auth
                @foreach ($mainLinks as $link)
                    @php
                        $isActive = collect($link['active'])->contains(fn($pattern) => request()->routeIs($pattern));
                    @endphp

                    <a class="app-nav-link {{ $isActive ? 'is-active' : '' }}" href="{{ route($link['route']) }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach

                @php
                    $managementIsActive = collect($managementLinks)
                        ->flatMap(fn($link) => $link['active'])
                        ->contains(fn($pattern) => request()->routeIs($pattern));
                @endphp

                <details class="app-nav-dropdown">
                    <summary class="app-nav-link {{ $managementIsActive ? 'is-active' : '' }}">
                        Gestión
                    </summary>

                    <div class="app-nav-dropdown-menu">
                        @foreach ($managementLinks as $link)
                            @php
                                $isActive = collect($link['active'])->contains(fn($pattern) => request()->routeIs($pattern));
                            @endphp

                            <a class="app-nav-dropdown-link {{ $isActive ? 'is-active' : '' }}"
                                href="{{ route($link['route']) }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endauth
        </nav>

        <div class="app-header-actions">
            @auth
                @if (app()->bound('tenant'))
                    <div class="app-company">
                        <span class="app-company-label">Empresa</span>
                        <span class="app-company-name">
                            {{ app('tenant')->name }}
                        </span>
                    </div>

                    @if (auth()->user()->tenants->count() > 1)
                        <a class="btn btn-secondary" href="{{ route('tenants.select') }}">
                            Cambiar
                        </a>
                    @endif
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-link" type="submit">
                        Cerrar sesión
                    </button>
                </form>
            @endauth
        </div>

    </div>
</header>