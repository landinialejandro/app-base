{{-- FILE: resources/views/assets/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nuevo activo')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Activos', 'url' => route('assets.index')],
            ['label' => 'Nuevo activo'],
        ]" />

        <x-page-header title="Nuevo activo" />

        <x-card>
            <form action="{{ route('assets.store') }}" method="POST" class="form">
                @include('assets._form')
            </form>
        </x-card>

    </x-page>
@endsection