{{-- FILE: resources/views/attachments/edit.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Editar adjunto')

@section('content')
    <x-page>
        <x-breadcrumb :items="$breadcrumbItems ?? []" />

        <x-page-header title="Editar adjunto" />

        <x-card>
            @include('attachments.partials.form', [
                'attachment' => $attachment,
                'attachableType' => $attachableType,
                'attachableId' => $attachableId,
                'returnTo' => $returnTo,
            ])
        </x-card>
    </x-page>
@endsection
