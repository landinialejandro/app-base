{{-- FILE: resources/views/attachments/edit.blade.php | V1 --}}

@extends('layouts.app')

@section('title', 'Editar adjunto')

@section('content')
    <x-page>
        <x-page-header title="Editar adjunto" />

        <x-card>
            @include('attachments.partials.form', [
                'mode' => 'edit',
                'attachment' => $attachment,
                'attachable' => $attachable,
                'action' => route('attachments.update', $attachment),
                'method' => 'PUT',
                'submitLabel' => 'Guardar cambios',
                'cancelUrl' => $cancelUrl,
                'returnTo' => $cancelUrl,
            ])
        </x-card>
    </x-page>
@endsection
