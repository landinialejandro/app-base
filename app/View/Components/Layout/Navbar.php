<?php

// FILE: app/View/Components/Layout/Navbar.php | V2

namespace App\View\Components\Layout;

use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Navigation\NavbarContext;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navbar extends Component
{
    public array $mainLinks;

    public array $managementLinks;

    public ?string $activeModule;

    public ?string $currentModule;

    public bool $managementIsActive;

    public bool $managementIsExpanded;

    public function __construct()
    {
        $this->mainLinks = [];
        $this->managementLinks = [];
        $this->activeModule = null;
        $this->currentModule = null;
        $this->managementIsActive = false;
        $this->managementIsExpanded = false;

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
            ->values();

        $context = NavbarContext::resolve(request());

        $this->activeModule = $context['active_module'] ?? null;
        $this->currentModule = $context['current_module'] ?? null;

        $this->mainLinks = $visibleLinks
            ->where('group', 'main')
            ->map(function (array $link) {
                return [
                    'module' => $link['module'],
                    'label' => $link['label'],
                    'route' => $link['route'],
                    'active' => $link['active'],
                ];
            })
            ->values()
            ->all();

        $this->managementLinks = $visibleLinks
            ->where('group', 'management')
            ->map(function (array $link) {
                return [
                    'module' => $link['module'],
                    'label' => $link['label'],
                    'route' => $link['route'],
                    'active' => $link['active'],
                ];
            })
            ->values()
            ->all();

        $managementModules = collect($this->managementLinks)
            ->pluck('module')
            ->values()
            ->all();

        $this->managementIsActive = in_array($this->activeModule, $managementModules, true);
        $this->managementIsExpanded = false;
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.navbar');
    }
}
