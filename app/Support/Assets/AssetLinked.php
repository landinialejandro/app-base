<?php

// FILE: app/Support/Assets/AssetLinked.php | V1

namespace App\Support\Assets;

use App\Models\Asset;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;

class AssetLinked
{
    public static function forAsset(?Asset $asset, array $trailQuery = [], string $label = 'Activo'): array
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $supported = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);

        if (! $supported) {
            return [
                'supported' => false,
                'exists' => false,
                'hidden' => true,
                'readonly' => false,
                'state' => 'hidden',
                'show_url' => null,
                'label' => $label,
                'text' => $label,
            ];
        }

        if (! $asset) {
            return [
                'supported' => true,
                'exists' => false,
                'hidden' => false,
                'readonly' => false,
                'state' => 'missing',
                'show_url' => null,
                'label' => $label,
                'text' => '—',
            ];
        }

        $canView = $user
            ? app(Security::class)->allows($user, 'assets.view', $asset)
            : false;

        return [
            'supported' => true,
            'exists' => true,
            'hidden' => false,
            'readonly' => ! $canView,
            'state' => $canView ? 'linked_viewable' : 'linked_readonly',
            'show_url' => $canView ? route('assets.show', ['asset' => $asset] + $trailQuery) : null,
            'label' => $label,
            'text' => $asset->name ?: $label,
        ];
    }
}
