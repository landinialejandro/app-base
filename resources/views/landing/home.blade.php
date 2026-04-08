{{-- FILE: resources/views/landing/home.blade.php | V3 --}}
@extends('layouts.landing')

@section('title', 'app-base')

@section('body')
    @include('landing.partials.header')

    <main>
        @include('landing.partials.hero')
        @include('landing.partials.problem')
        @include('landing.partials.value')
        @include('landing.partials.business-types')
        @include('landing.partials.adaptable-agenda')
        @include('landing.partials.modules')
        @include('landing.partials.flow')
        @include('landing.partials.trust')
        @include('landing.partials.cta')
    </main>

    @include('landing.partials.footer')
@endsection
