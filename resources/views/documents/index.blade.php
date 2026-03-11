{{-- FILE: resources/views/documents/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Documentos')

@section('content')
    <x-page class="list-page">
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Documentos'],
        ]" />

        <x-page-header title="Documentos">
            <a href="{{ route('documents.create') }}" class="btn btn-primary">
                Nuevo documento
            </a>
        </x-page-header>

        <x-card class="list-card">
            @if ($documents->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Contacto</th>
                                <th>Fecha</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $document)
                                <tr>
                                    <td>
                                        <a href="{{ route('documents.show', $document) }}">
                                            {{ $document->number ?: 'Sin número' }}
                                        </a>
                                    </td>
                                    <td>{{ $document->kind }}</td>
                                    <td>{{ $document->status }}</td>
                                    <td>{{ $document->party?->name ?: '—' }}</td>
                                    <td>{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</td>
                                    <td>${{ number_format($document->total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">No hay documentos cargados.</p>
            @endif
        </x-card>
    </x-page>
@endsection