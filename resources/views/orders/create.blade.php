{{-- FILE: resources/views/orders/create.blade.php | V11 --}}

@extends('layouts.app')

@php
    use App\Support\Catalogs\OrderCatalog;

    $groupLocked = $groupLocked ?? false;
    $isServiceContext = ($prefilledGroup ?? null) === OrderCatalog::GROUP_SERVICE;
    $pageTitle = $isServiceContext ? 'Nueva orden de servicio' : 'Nueva orden';
    $submitLabel = $isServiceContext ? 'Crear orden de servicio' : 'Crear orden';
    $storeRouteName = $groupLocked && $isServiceContext ? 'service.orders.store' : 'orders.store';
@endphp

@section('title', $pageTitle)

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $fallbackBackUrl = $groupLocked && $isServiceContext
            ? route('service.orders.index')
            : route('orders.index');
        $backUrl = NavigationTrail::previousUrl($navigationTrail, $fallbackBackUrl);
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$pageTitle">
            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route($storeRouteName, $trailQuery) }}">
                @csrf

                @include('orders._form', [
                    'prefilledGroup' => $prefilledGroup,
                    'prefilledKind' => $prefilledKind,
                    'relationshipBoundary' => $relationshipBoundary,
                    'groupLocked' => $groupLocked,
                ])

                <div class="form-actions">
                    <x-button-primary type="submit">
                        {{ $submitLabel }}
                    </x-button-primary>

                    <x-button-secondary :href="$backUrl">
                        Cancelar
                    </x-button-secondary>
                </div>
            </form>
        </x-card>
    </x-page>

    <x-dev-component-version name="orders.create" version="V11" align="right" />
@endsection