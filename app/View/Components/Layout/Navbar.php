<?php

// FILE: app/View/Components/Layout/Navbar.php | V3

namespace App\View\Components\Layout;

use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Navigation\NavbarContext;
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

    public function __construct()
    {
        $this->quickLinks = [];
        $this->secondaryLinks = [];
        $this->activeModule = null;
        $this->currentModule = null;
        $this->secondaryIsActive = false;
        $this->secondaryIsExpanded = false;

        $user = auth()->user();
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if (! $user || ! $tenant) {
            return;
        }

        $resolver = app(RolePermissionResolver::class);

        $visibleLinks = collect(ModuleCatalog::navDefinitions())
            ->filter(function (array $link) use ($resolver, $tenant, $user) {
                return $resolver->canUseModule($link['module'], $tenant, $user);
            })
            ->map(function (array $link) {
                if ($link['module'] === 'appointments') {
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

        $quickModules = ['appointments', 'parties', 'assets'];

        $quickLinks = $this->orderedLinks($visibleLinks, $quickModules);
        $secondaryLinks = $visibleLinks
            ->reject(fn (array $link) => in_array($link['module'], $quickModules, true))
            ->values()
            ->all();

        $this->quickLinks = $quickLinks->all();
        $this->secondaryLinks = $secondaryLinks;

        $secondaryModules = collect($this->secondaryLinks)
            ->pluck('module')
            ->values()
            ->all();

        $this->secondaryIsActive = in_array($this->activeModule, $secondaryModules, true);
        $this->secondaryIsExpanded = false;
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
