{{-- FILE: resources/views/assets/components/maintenance-asset-card.blade.php | V1 --}}

@props([
    'visible' => true,
    'asset' => null,
    'linked' => [],
])

@php
    use App\Support\Catalogs\AssetCatalog;

    $state = $linked['state'] ?? 'hidden';
    $showUrl = $linked['show_url'] ?? null;
@endphp

@if ($visible)
    @if ($asset)
        <div class="detail-block">
            <div>
                @if ($state === 'linked_viewable' && $showUrl)
                    <a href="{{ $showUrl }}">
                        {{ $asset->name }}
                    </a>
                @else
                    {{ $asset->name }}
                @endif
            </div>

            <div class="form-help">
                {{ AssetCatalog::kindLabel($asset->kind) }}

                @if ($asset->internal_code)
                    · Código: {{ $asset->internal_code }}
                @endif

                @if ($asset->relationship_type)
                    · {{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}
                @endif

                @if ($asset->status)
                    · {{ AssetCatalog::statusLabel($asset->status) }}
                @endif

                @if ($asset->party)
                    · Contacto: {{ $asset->party->display_name ?: $asset->party->name }}
                @endif
            </div>
        </div>
    @else
        —
    @endif
@endif