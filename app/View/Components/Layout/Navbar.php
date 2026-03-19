<?php

// FILE: app/View/Components/Layout/Navbar.php

namespace App\View\Components\Layout;

use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navbar extends Component
{
    public array $mainLinks;

    public array $managementLinks;

    public function __construct()
    {
        $this->mainLinks = [];
        $this->managementLinks = [];

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
    }

    public function render(): View|Closure|string
    {
        return view('components.layout.navbar');
    }
}
