{{-- FILE: resources/views/orders/edit.blade.php | V8 --}}

@extends('layouts.app')

@section('title', 'Editar orden')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('orders.show', ['order' => $order]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar orden">
            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('orders.update', ['order' => $order] + $trailQuery) }}">
                @csrf
                @method('PUT')

                @include('orders._form', [
                    'order' => $order,
                ])

                <div class="form-actions">
                    <x-button-primary type="submit">
                        Guardar cambios
                    </x-button-primary>

                    <x-button-secondary :href="$backUrl">
                        Cancelar
                    </x-button-secondary>
                </div>
            </form>
        </x-card>
    </x-page>

    <x-dev-component-version name="orders.edit" version="V8" align="right" />
@endsection