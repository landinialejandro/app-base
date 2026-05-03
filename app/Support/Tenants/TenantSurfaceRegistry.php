<?php

// FILE: app/Support/Tenants/TenantSurfaceRegistry.php | V1

namespace App\Support\Tenants;

use App\Support\Modules\Contracts\ModuleSurfaceService;

class TenantSurfaceRegistry
{
    /**
     * Services transversales tenant-scoped que publican surfaces sin ser módulos funcionales.
     *
     * @return array<int, class-string>
     */
    protected function surfaceServices(): array
    {
        return [
            OperationalActivitySurfaceService::class,
        ];
    }

    /**
     * Devuelve offers transversales no modulares compatibles con ModuleSurfaceRegistry.
     *
     * @return array<int, array<string, mixed>>
     */
    public function offers(): array
    {
        return collect($this->surfaceServices())
            ->map(fn (string $serviceClass) => app($serviceClass))
            ->filter(fn ($service) => $service instanceof ModuleSurfaceService)
            ->flatMap(fn (ModuleSurfaceService $service) => $service->offers())
            ->filter(fn ($offer) => is_array($offer))
            ->map(fn (array $offer) => array_merge([
                'module' => 'operational_activity',
                'module_label' => 'Actividad',
                'module_icon' => null,
                'priority' => 999,
                'visible' => true,
                'targets' => [],
                'needs' => [],
                'slot' => null,
            ], $offer))
            ->values()
            ->all();
    }
}