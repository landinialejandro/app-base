<?php

// FILE: app/View/Components/Layout/Navbar.php | V5

namespace App\View\Components\Layout;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Navigation\NavbarContext;
use App\Support\Tenants\TenantProfileAccess;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Navbar extends Component
{
    public array $quickLinks;

    public array $secondaryLinks;

    public ?string $activeModule;

    public ?string $currentModule;

    public bool $secondaryIsActive;

    public bool $secondaryIsExpanded;

    public string $brandUrl;

    public ?string $tenantName;

    public ?string $userName;

    public ?string $logoutUrl;

    public array $userMenuLinks;

    public function __construct()
    {
        $this->quickLinks = [];
        $this->secondaryLinks = [];
        $this->activeModule = null;
        $this->currentModule = null;
        $this->secondaryIsActive = false;
        $this->secondaryIsExpanded = false;
        $this->brandUrl = url('/');
        $this->tenantName = null;
        $this->userName = null;
        $this->logoutUrl = null;
        $this->userMenuLinks = [];

        $user = auth()->user();
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if (! $user) {
            return;
        }

        $this->brandUrl = route('dashboard');
        $this->tenantName = $tenant?->name;
        $this->userName = $user->name;
        $this->logoutUrl = route('logout');
        $this->userMenuLinks = $this->buildUserMenuLinks($user, $tenant);

        if (! $tenant) {
            return;
        }

        $resolver = app(RolePermissionResolver::class);

        $visibleLinks = collect(ModuleCatalog::navDefinitions())
            ->filter(function (array $link) use ($resolver, $tenant, $user) {
                return $resolver->canUseModule($link['module'], $tenant, $user);
            })
            ->map(function (array $link) {
                if ($link['module'] === ModuleCatalog::APPOINTMENTS) {
                    $link['label'] = 'Agenda';
                    $link['route'] = 'appointments.calendar';
                }

                return [
                    'module' => $link['module'],
                    'label' => $link['label'],
                    'route' => $link['route'],
                    'active' => $link['active'] ?? null,
                    'icon' => $link['icon'] ?? ModuleCatalog::icon($link['module']),
                ];
            })
            ->values();

        $context = NavbarContext::resolve(request());

        $this->activeModule = $context['active_module'] ?? null;
        $this->currentModule = $context['current_module'] ?? null;

        $quickModules = [
            ModuleCatalog::APPOINTMENTS,
            ModuleCatalog::PARTIES,
            ModuleCatalog::ASSETS,
        ];

        $quickLinks = $this->orderedLinks($visibleLinks, $quickModules);
        $secondaryLinks = $visibleLinks
            ->reject(fn (array $link) => in_array($link['module'], $quickModules, true))
            ->values();

        $this->quickLinks = $quickLinks
            ->map(fn (array $link) => $this->prepareNavLink($link))
            ->all();

        $this->secondaryLinks = $secondaryLinks
            ->map(fn (array $link) => $this->prepareNavLink($link))
            ->values()
            ->all();

        $secondaryModules = collect($this->secondaryLinks)
            ->pluck('module')
            ->values()
            ->all();

        $this->secondaryIsActive = in_array($this->activeModule, $secondaryModules, true);
        $this->secondaryIsExpanded = false;
    }

    protected function buildUserMenuLinks(User $user, ?Tenant $tenant): array
    {
        $links = [
            $this->prepareUserMenuLink('Perfil', 'profile.show'),
        ];

        if ($tenant) {
            $tenantProfileAccess = app(TenantProfileAccess::class);
            $currentMembership = $tenantProfileAccess->actorMembershipFor($user);

            if ($tenantProfileAccess->canViewProfile($currentMembership)) {
                $links[] = $this->prepareUserMenuLink('Perfil de empresa', 'tenant.profile.show');
            }
        }

        if ($user->tenants()->count() > 1) {
            $links[] = $this->prepareUserMenuLink('Cambiar empresa', 'tenants.select');
        }

        return $links;
    }

    protected function prepareUserMenuLink(string $label, string $route): array
    {
        return [
            'label' => $label,
            'url' => route($route),
            'is_active' => request()->routeIs($route),
        ];
    }

    protected function prepareNavLink(array $link): array
    {
        $icon = $link['icon'] ?? 'box';

        return $link + [
            'url' => route($link['route']),
            'is_active' => $this->activeModule === $link['module'],
            'is_current' => $this->currentModule === $link['module'],
            'icon_component' => 'icons.'.$icon,
        ];
    }

    protected function orderedLinks(Collection $visibleLinks, array $modules): Collection
    {
        return collect($modules)
            ->map(function (string $module) use ($visibleLinks) {
                return $visibleLinks->firstWhere('module', $module);
            })
            ->filter()
            ->values();
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.navbar');
    }
}
