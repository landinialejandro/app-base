{{-- FILE: resources/views/documents/items/edit.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Editar ítem')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('documents.show', ['document' => $document]));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar ítem" />

        <x-card>
            <form method="POST"
                action="{{ route('documents.items.update', ['document' => $document, 'item' => $item] + $trailQuery) }}"
                class="form">
                @csrf
                @method('PUT')

                @include('documents.items._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
