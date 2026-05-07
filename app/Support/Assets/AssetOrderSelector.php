<?php

// FILE: app/Support/Assets/AssetOrderSelector.php | V2

namespace App\Support\Assets;

use App\Models\Asset;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Catalogs\AssetCatalog;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Support\Collection;

class AssetOrderSelector
{
    public function optionsFor(User $user): Collection
    {
        return app(Security::class)
            ->scope($user, ModuleCatalog::ASSETS.'.viewAny', Asset::query())
            ->with('party')
            ->orderBy('name')
            ->get()
            ->map(function (Asset $asset) {
                $meta = collect([
                    AssetCatalog::kindLabel($asset->kind),
                    $asset->internal_code ? 'Código: '.$asset->internal_code : null,
                    $asset->party?->name ? 'Contacto: '.$asset->party->name : null,
                ])->filter()->implode(' · ');

                return [
                    'id' => $asset->id,
                    'party_id' => $asset->party_id,
                    'label' => $asset->name,
                    'meta' => $meta,
                ];
            });
    }
}