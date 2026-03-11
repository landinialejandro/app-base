@extends('layouts.app')

@section('title', 'Nueva orden')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Órdenes', 'url' => route('orders.index')],
            ['label' => 'Nueva orden'],
        ]" />

        <x-page-header title="Nueva orden" />

        <x-card>
            <form method="POST" action="{{ route('orders.store') }}" class="form">
                @csrf

                @include('orders._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection