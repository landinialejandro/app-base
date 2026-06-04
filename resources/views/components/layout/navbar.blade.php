{{-- FILE: resources/views/components/layout/navbar.blade.php | V8 --}}

<header class="app-header">
    <div class="container app-header-inner">

        <div class="app-brand">
            <a href="{{ $brandUrl }}" class="app-brand-link" aria-label="app-base">
                <span class="app-brand__icon" aria-hidden="true">
                    @include('svg.app-logo')
                </span>
            </a>
        </div>

        <nav class="app-nav">
            @if ($userName)
                @if (count($secondaryLinks))
                    <details class="app-nav-dropdown" @if ($secondaryIsExpanded) open @endif>
                        <summary class="app-nav-link app-nav-link--with-icon {{ $secondaryIsActive ? 'is-active' : '' }}">
                            <span class="app-nav-link__icon" aria-hidden="true">
                                <x-icons.list-check />
                            </span>
                            <span>Gestión</span>
                        </summary>

                        <div class="app-nav-dropdown-menu">
                            @foreach ($secondaryLinks as $link)
                                <a class="app-nav-dropdown-link {{ $link['is_active'] ? 'is-active' : '' }}"
                                    href="{{ $link['url'] }}"
                                    @if ($link['is_current']) aria-current="page" @endif>
                                    <span class="app-nav-link__icon" aria-hidden="true">
                                        <x-dynamic-component :component="$link['icon_component']" />
                                    </span>
                                    <span>{{ $link['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif

                @foreach ($quickLinks as $link)
                    <a class="app-nav-link app-nav-link--with-icon {{ $link['is_active'] ? 'is-active' : '' }}"
                        href="{{ $link['url'] }}" @if ($link['is_current']) aria-current="page" @endif>
                        <span class="app-nav-link__icon" aria-hidden="true">
                            <x-dynamic-component :component="$link['icon_component']" />
                        </span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="app-header-actions">
            @if ($userName)
                @if ($tenantName)
                    <div class="app-company">
                        <span class="app-company-label">Empresa</span>
                        <span class="app-company-name">{{ $tenantName }}</span>
                    </div>
                @endif

                <details class="app-user-dropdown">
                    <summary class="app-user-trigger">
                        <span class="app-user-trigger-icon" aria-hidden="true">
                            <x-icons.user-group />
                        </span>

                        <span class="app-user-trigger-text">
                            <span class="app-user-trigger-label">Usuario</span>
                            <span class="app-user-trigger-name">{{ $userName }}</span>
                        </span>
                    </summary>

                    <div class="app-user-dropdown-menu">
                        @foreach ($userMenuLinks as $link)
                            <a href="{{ $link['url'] }}"
                                class="app-user-dropdown-link {{ $link['is_active'] ? 'is-active' : '' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach

                        <div class="app-user-dropdown-divider"></div>

                        <form method="POST" action="{{ $logoutUrl }}">
                            @csrf
                            <button class="app-user-dropdown-button" type="submit">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </details>
            @endif
        </div>

    </div>
</header>
