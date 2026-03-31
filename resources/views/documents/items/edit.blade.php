{{-- FILE: resources/views/documents/items/edit.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Editar ítem')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $showTrail = NavigationTrail::sliceBefore($navigationTrail, 'documents.items.edit', $item->id);
        $cancelUrl = route('documents.show', ['document' => $document] + NavigationTrail::toQuery($showTrail));
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
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
