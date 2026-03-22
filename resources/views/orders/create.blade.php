{{-- FILE: resources/views/orders/create.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Nueva orden')

@section('content')
    @php
        $contextRouteParams = $navigationContext
            ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
            : [];

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($navigationContext['type'] ?? null) === 'appointment') {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];
            $breadcrumbItems[] = ['label' => 'Nueva orden'];
        } else {
            $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
            $breadcrumbItems[] = ['label' => 'Nueva orden'];
        }
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />
        <x-page-header title="Nueva orden" />

        <x-card>
            <form method="POST" action="{{ route('orders.store', $contextRouteParams) }}" class="form">
                @csrf

                @include('orders._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>

                    @if (($navigationContext['type'] ?? null) === 'appointment')
                        <a href="{{ $navigationContext['url'] }}" class="btn btn-secondary">Cancelar</a>
                    @else
                        <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancelar</a>
                    @endif
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
