{{-- FILE: resources/views/assets/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Editar activo')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Activos', 'url' => route('assets.index')],
            ['label' => $asset->name, 'url' => route('assets.show', $asset)],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar activo" />

        <x-card>
            <form action="{{ route('assets.update', $asset) }}" method="POST" class="form">
                @method('PUT')
                @include('assets._form', ['asset' => $asset])
            </form>
        </x-card>

    </x-page>
@endsection