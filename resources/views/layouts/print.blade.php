{{-- FILE: resources/views/layouts/print.blade.php | V3 --}}
@php
    $renderMode = $renderMode ?? 'print';
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Impresión')</title>

    @if ($renderMode === 'pdf')
        <style>
            {!! file_get_contents(public_path('css/modules/print.css')) !!}
        </style>
    @else
        <link rel="stylesheet" href="{{ asset('css/modules/print.css') }}">
    @endif
</head>

<body class="print-body {{ $renderMode === 'pdf' ? 'print-body--pdf' : 'print-body--html' }}">
    <div class="print-page">
        @if ($renderMode !== 'pdf')
            <div class="print-toolbar" data-action="app-print-toolbar">
                <button type="button" class="print-toolbar-button" data-print-action="print">
                    Imprimir
                </button>

                <button type="button" class="print-toolbar-button" data-print-action="close">
                    Cerrar
                </button>
            </div>
        @endif

        <div class="print-document">
            @include('print.partials.header')

            @yield('content')

            @include('print.partials.footer')
        </div>
    </div>

    @if ($renderMode !== 'pdf')
        <script src="{{ asset('js/print.js') }}"></script>
    @endif
</body>

</html>
