{{-- FILE: resources/views/documents/items/create.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Agregar ítem')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('documents.show', ['document' => $document]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Agregar ítem" />

        <x-card>
            <form method="POST" action="{{ route('documents.items.store', ['document' => $document] + $trailQuery) }}"
                class="form">
                @csrf

                @include('documents.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
