{{-- FILE: resources/views/attachments/create.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Agregar adjunto')

@section('content')
    <x-page>
        <x-breadcrumb :items="$breadcrumbItems ?? []" />

        <x-page-header title="Agregar adjunto" />

        <x-card>
            @include('attachments.partials.form', [
                'attachableType' => $attachableType,
                'attachableId' => $attachableId,
                'returnTo' => $returnTo,
            ])
        </x-card>
    </x-page>
@endsection
