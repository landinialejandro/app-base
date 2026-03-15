{{-- FILE: resources/views/orders/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Órdenes')

@section('content')

    @php
        use App\Support\Catalogs\OrderCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Órdenes']]" />

        <x-page-header title="Órdenes">
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                Nueva orden
            </a>
        </x-page-header>

        <x-card class="list-card">

            @if ($orders->count())

                <div class="table-wrap list-scroll">

                    <table class="table">

                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Contacto</th>
                                <th>Activo</th>
                                <th>Fecha</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($orders as $order)
                                <tr>

                                    <td>
                                        <a href="{{ route('orders.show', $order) }}">
                                            {{ $order->number ?: 'Sin número' }}
                                        </a>
                                    </td>

                                    <td>
                                        {{ OrderCatalog::label($order->kind) }}
                                    </td>

                                    <td>
                                        <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                                            {{ OrderCatalog::label($order->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $order->party?->name ?: '—' }}
                                    </td>

                                    <td>
                                        {{ $order->asset?->name ?: '—' }}
                                    </td>

                                    <td>
                                        {{ $order->ordered_at?->format('d/m/Y') ?: '—' }}
                                    </td>

                                    <td>
                                        ${{ number_format($order->total, 2, ',', '.') }}
                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>
            @else
                <p class="mb-0">No hay órdenes cargadas.</p>

            @endif

        </x-card>

    </x-page>

@endsection
