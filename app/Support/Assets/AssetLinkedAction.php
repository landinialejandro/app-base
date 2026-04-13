<?php

// FILE: app/Support/Assets/AssetLinkedAction.php | V1

namespace App\Support\Assets;

use App\Models\Asset;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;

class AssetLinkedAction
{
    public static function forAsset(?Asset $asset, array $trailQuery = [], string $label = 'Activo'): array
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $supported = TenantModuleAccess::isEnabled(ModuleCatalog::ASSETS, $tenant);

        if (! $supported) {
            return [
                'supported' => false,
                'linked' => false,
                'can_view' => false,
                'show_url' => null,
                'label' => $label,
                'linked_text' => $label,
            ];
        }

        if (! $asset) {
            return [
                'supported' => true,
                'linked' => false,
                'can_view' => false,
                'show_url' => null,
                'label' => $label,
                'linked_text' => '—',
            ];
        }

        $canView = $user
            ? app(Security::class)->allows($user, 'assets.view', $asset)
            : false;

        return [
            'supported' => true,
            'linked' => true,
            'can_view' => $canView,
            'show_url' => $canView ? route('assets.show', ['asset' => $asset] + $trailQuery) : null,
            'label' => $label,
            'linked_text' => $asset->name ?: $label,
        ];
    }
}
