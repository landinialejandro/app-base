{{-- FILE: resources/views/layouts/print.blade.php | V1 --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Impresión')</title>

    <link rel="stylesheet" href="{{ asset('css/modules/print.css') }}">
</head>

<body>
    <div class="print-page">
        <div class="print-toolbar" data-action="app-print-toolbar">
            <button type="button" class="print-toolbar-button" data-print-action="print">
                Imprimir
            </button>

            <button type="button" class="print-toolbar-button" data-print-action="close">
                Cerrar
            </button>
        </div>

        <div class="print-document">
            @include('print.partials.header')

            @yield('content')

            @include('print.partials.footer')
        </div>
    </div>

    <script src="{{ asset('js/print.js') }}"></script>
</body>

</html>
