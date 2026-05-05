{{-- FILE: resources/views/orders/create.blade.php | V8 --}}

@extends('layouts.app')

@section('title', 'Nueva orden')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.index'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nueva orden">
            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('orders.store', $trailQuery) }}">
                @csrf

                @include('orders._form', [
                    'prefilledGroup' => $prefilledGroup,
                    'prefilledKind' => $prefilledKind,
                ])

                <div class="form-actions">
                    <x-button-primary type="submit">
                        Crear orden
                    </x-button-primary>

                    <x-button-secondary :href="$backUrl">
                        Cancelar
                    </x-button-secondary>
                </div>
            </form>
        </x-card>
    </x-page>

    <x-dev-component-version name="orders.create" version="V8" align="right" />
@endsection