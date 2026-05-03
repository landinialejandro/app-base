<?php

// FILE: app/Support/Modules/ModuleSurfaceRegistry.php | V5

namespace App\Support\Modules;

use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class ModuleSurfaceRegistry
{
    /**
     * Slots válidos del host show.
     *
     * @var array<int, string>
     */
    protected array $knownSlots = [
        'header_actions',
        'summary_items',
        'detail_items',
        'tab_nav',
        'tab_panels',
    ];

    /**
     * Devuelve las surfaces aplicables para un solicitante/host concreto.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
public function surfacesFor(string $host, array $context = []): array
{
    if ($host === '') {
        return [];
    }

    if (! array_key_exists('host', $context)) {
        $context['host'] = $host;
    }

    if (! array_key_exists('trailQuery', $context)) {
        $context['trailQuery'] = [];
    }

    $moduleOffers = collect(ModuleCatalog::all())
        ->map(fn (string $module) => $this->offersFromModule($module))
        ->flatten(1);

    $tenantOffers = collect(app(\App\Support\Tenants\TenantSurfaceRegistry::class)->offers());

    return $moduleOffers
        ->merge($tenantOffers)
        ->filter(fn ($offer) => is_array($offer))
        ->filter(fn (array $offer) => $this->matchesHost($offer, $host))
        ->filter(fn (array $offer) => $this->hasRequiredContext($offer, $context))
        ->map(fn (array $offer) => $this->normalizeOffer($offer, $host, $context))
        ->sortBy(fn (array $surface) => $surface['priority'] ?? 999)
        ->values()
        ->all();
}

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function embeddedFor(string $host, array $context = []): array
    {
        return $this->filterByType(
            $this->surfacesFor($host, $context),
            'embedded'
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function linkedFor(string $host, array $context = []): array
    {
        return $this->filterByType(
            $this->surfacesFor($host, $context),
            'linked'
        );
    }

    /**
     * Devuelve solo las surfaces del slot solicitado.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function slotFor(string $host, string $slot, array $context = []): array
    {
        return $this->filterBySlot(
            $this->surfacesFor($host, $context),
            $slot
        );
    }

    /**
     * Devuelve las surfaces agrupadas por slot del host.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupedFor(string $host, array $context = []): array
    {
        $surfaces = $this->surfacesFor($host, $context);

        $grouped = [];

        foreach ($this->knownSlots as $slot) {
            $grouped[$slot] = $this->filterBySlot($surfaces, $slot);
        }

        return $grouped;
    }

    /**
     * @param  array<int, array<string, mixed>>  $surfaces
     * @return array<int, array<string, mixed>>
     */
    protected function filterByType(array $surfaces, string $type): array
    {
        return collect($surfaces)
            ->where('type', $type)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $surfaces
     * @return array<int, array<string, mixed>>
     */
    protected function filterBySlot(array $surfaces, string $slot): array
    {
        return collect($surfaces)
            ->where('slot', $slot)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function offersFromModule(string $module): array
    {
        $serviceClass = ModuleCatalog::surfaceService($module);

        if ($serviceClass === null) {
            return [];
        }

        if (! app(TenantModuleAccess::class)->isEnabled($module)) {
            return [];
        }

        $service = app($serviceClass);

        if (! $service instanceof ModuleSurfaceService) {
            return [];
        }

        return collect($service->offers())
            ->filter(fn ($offer) => is_array($offer))
            ->map(fn (array $offer) => array_merge([
                'module' => $module,
                'module_label' => ModuleCatalog::label($module),
                'module_icon' => ModuleCatalog::icon($module),
                'priority' => 999,
                'visible' => true,
                'targets' => [],
                'needs' => [],
                'slot' => null,
            ], $offer))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    protected function matchesHost(array $offer, string $host): bool
    {
        $targets = $offer['targets'] ?? [];

        if (! is_array($targets) || $targets === []) {
            return false;
        }

        return in_array($host, $targets, true);
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $context
     */
    protected function hasRequiredContext(array $offer, array $context): bool
    {
        $needs = $offer['needs'] ?? [];

        if (! is_array($needs) || $needs === []) {
            return true;
        }

        foreach ($needs as $key) {
            if (! array_key_exists($key, $context) || $context[$key] === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $hostPack
     * @return array<string, mixed>
     */
    protected function normalizeOffer(array $offer, string $host, array $hostPack = []): array
    {
        $resolved = [];

        if (isset($offer['resolver']) && is_callable($offer['resolver'])) {
            $resolved = (array) call_user_func($offer['resolver'], $hostPack);
        }

        $type = $offer['type'] ?? null;

        unset($offer['targets'], $offer['needs'], $offer['resolver']);

        $defaults = [
            'host' => $host,
            'priority' => 999,
            'visible' => true,
            'slot' => null,
        ];

        if ($type === 'embedded') {
            $defaults = array_merge($defaults, [
                'count' => 0,
                'data' => [],
            ]);
        }

        return array_merge($defaults, $offer, $resolved);
    }
}
