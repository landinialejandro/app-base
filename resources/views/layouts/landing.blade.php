{{-- FILE: resources/views/layouts/landing.blade.php | V1 --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'app-base')</title>
    <meta name="description"
        content="Sistema SaaS modular para organizar la operación de tu empresa con claridad, continuidad y control.">

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @stack('head')
</head>

<body class="landing-body">
    @yield('body')

    <script src="{{ asset('js/landing.js') }}"></script>
    @stack('scripts')
</body>

</html>
