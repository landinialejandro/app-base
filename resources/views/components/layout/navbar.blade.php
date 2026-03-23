{{-- FILE: resources/views/components/layout/navbar.blade.php | V2 --}}

@php
    $user = auth()->user();
    $tenant = app()->bound('tenant') ? app('tenant') : null;

    $currentMembership = $user && $tenant ? $user->memberships()->where('tenant_id', $tenant->id)->first() : null;
@endphp

<header class="app-header">
    <div class="container app-header-inner">

        <div class="app-brand">
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}">
                app-base
            </a>
        </div>

        <nav class="app-nav">
            @auth
                @if (count($managementLinks))
                    <details class="app-nav-dropdown" @if ($managementIsExpanded) open @endif>
                        <summary class="app-nav-link {{ $managementIsActive ? 'is-active' : '' }}">
                            Gestión
                        </summary>

                        <div class="app-nav-dropdown-menu">
                            @foreach ($managementLinks as $link)
                                @php
                                    $isActive = $activeModule === $link['module'];
                                    $isCurrent = $currentModule === $link['module'];
                                @endphp

                                <a class="app-nav-dropdown-link {{ $isActive ? 'is-active' : '' }}"
                                    href="{{ route($link['route']) }}"
                                    @if ($isCurrent) aria-current="page" @endif>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif

                @foreach ($mainLinks as $link)
                    @php
                        $isActive = $activeModule === $link['module'];
                        $isCurrent = $currentModule === $link['module'];
                    @endphp

                    <a class="app-nav-link {{ $isActive ? 'is-active' : '' }}" href="{{ route($link['route']) }}"
                        @if ($isCurrent) aria-current="page" @endif>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            @endauth
        </nav>

        <div class="app-header-actions">
            @auth
                @if ($tenant)
                    <div class="app-company">
                        <span class="app-company-label">Empresa</span>
                        <span class="app-company-name">{{ $tenant->name }}</span>
                    </div>
                @endif

                <details class="app-user-dropdown">
                    <summary class="app-user-trigger">
                        <span class="app-user-trigger-text">
                            <span class="app-user-trigger-label">Usuario</span>
                            <span class="app-user-trigger-name">{{ $user->name }}</span>
                        </span>
                    </summary>

                    <div class="app-user-dropdown-menu">
                        <a href="{{ route('profile.show') }}"
                            class="app-user-dropdown-link {{ request()->routeIs('profile.show') ? 'is-active' : '' }}">
                            Perfil
                        </a>

                        @if ($tenant && $currentMembership?->is_owner)
                            <a href="{{ route('tenant.profile.show') }}"
                                class="app-user-dropdown-link {{ request()->routeIs('tenant.profile.show') ? 'is-active' : '' }}">
                                Perfil de empresa
                            </a>
                        @endif

                        @if ($user->tenants->count() > 1)
                            <a href="{{ route('tenants.select') }}"
                                class="app-user-dropdown-link {{ request()->routeIs('tenants.select') ? 'is-active' : '' }}">
                                Cambiar empresa
                            </a>
                        @endif

                        <div class="app-user-dropdown-divider"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="app-user-dropdown-button" type="submit">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </details>
            @endauth
        </div>

    </div>
</header>
