{{-- FILE: resources/views/dashboard.blade.php | V15 --}}

@extends('layouts.app')

@section('title', 'Dashboard')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/modules/dashboard.css') }}">
@endpush

@section('content')
    <x-page>
        <x-breadcrumb :items="[['label' => 'Inicio']]" />

        <x-page-header title="Dashboard" />

        @foreach ($dashboardSections as $section)
            @include('dashboard.partials.section', ['section' => $section])
        @endforeach

        <x-dev-component-version name="dashboard" version="V15" align="right" />
    </x-page>
@endsection
