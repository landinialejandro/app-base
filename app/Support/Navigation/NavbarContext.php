<?php

// FILE: app/Support/Navigation/NavbarContext.php | V1

namespace App\Support\Navigation;

use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NavbarContext
{
    public static function resolve(Request $request): array
    {
        $currentRouteName = $request->route()?->getName();
        $currentModule = static::resolveModuleFromRouteName($currentRouteName);

        $trail = NavigationTrail::fromRequest($request);
        $contextModule = static::resolveContextModuleFromTrail($trail);

        return [
            'current_route_name' => $currentRouteName,
            'current_module' => $currentModule,
            'context_module' => $contextModule,
            'active_module' => $contextModule ?: $currentModule,
        ];
    }

    public static function resolveContextModuleFromTrail(array $trail): ?string
    {
        foreach (NavigationTrail::normalize($trail) as $node) {
            $module = static::resolveModuleFromNodeKey($node['key'] ?? null);

            if ($module !== null) {
                return $module;
            }
        }

        return null;
    }

    public static function resolveModuleFromNodeKey(?string $key): ?string
    {
        if (! is_string($key) || trim($key) === '') {
            return null;
        }

        $prefix = trim(Str::before($key, '.'));

        if ($prefix === '' || $prefix === ModuleCatalog::DASHBOARD) {
            return null;
        }

        foreach (ModuleCatalog::navDefinitions() as $definition) {
            if (($definition['module'] ?? null) === $prefix) {
                return $prefix;
            }
        }

        return null;
    }

    public static function resolveModuleFromRouteName(?string $routeName): ?string
    {
        if (! is_string($routeName) || trim($routeName) === '') {
            return null;
        }

        foreach (ModuleCatalog::navDefinitions() as $definition) {
            foreach (($definition['active'] ?? []) as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $definition['module'] ?? null;
                }
            }
        }

        return null;
    }
}
