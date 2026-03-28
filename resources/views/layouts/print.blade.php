{{-- FILE: resources/views/layouts/print.blade.php | V1 --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Impresión')</title>

    <style>
        :root {
            --print-border: #d1d5db;
            --print-text: #111827;
            --print-muted: #6b7280;
            --print-soft: #f3f4f6;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--print-text);
            background: #fff;
            font-size: 12px;
            line-height: 1.45;
        }

        body {
            padding: 24px;
        }

        .print-page {
            max-width: 900px;
            margin: 0 auto;
        }

        .print-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 16px;
        }

        .print-toolbar-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 14px;
            border: 1px solid var(--print-border);
            border-radius: 6px;
            background: #fff;
            color: var(--print-text);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .print-toolbar-button:hover {
            background: var(--print-soft);
        }

        .print-document {
            border: 1px solid var(--print-border);
            border-radius: 10px;
            padding: 24px;
        }

        .print-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 20px;
            align-items: start;
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--print-border);
        }

        .print-company-name {
            margin: 0;
            font-size: 22px;
            line-height: 1.15;
        }

        .print-company-meta {
            margin-top: 6px;
            color: var(--print-muted);
        }

        .print-company-meta div+div {
            margin-top: 2px;
        }

        .print-logo-wrap {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }

        .print-logo {
            max-width: 180px;
            max-height: 80px;
            object-fit: contain;
            display: block;
        }

        .print-title-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .print-title {
            margin: 0;
            font-size: 20px;
            line-height: 1.15;
        }

        .print-subtitle {
            margin-top: 4px;
            color: var(--print-muted);
            font-size: 12px;
        }

        .print-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border: 1px solid var(--print-border);
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .print-section+.print-section {
            margin-top: 20px;
        }

        .print-section-title {
            margin: 0 0 10px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--print-muted);
        }

        .print-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .print-grid--2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .print-block {
            border: 1px solid var(--print-border);
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
        }

        .print-block-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--print-muted);
            margin-bottom: 4px;
        }

        .print-block-value {
            word-break: break-word;
        }

        .print-notes {
            border: 1px solid var(--print-border);
            border-radius: 8px;
            padding: 12px;
            min-height: 72px;
            white-space: pre-wrap;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
        }

        .print-table th,
        .print-table td {
            border: 1px solid var(--print-border);
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        .print-table th {
            background: var(--print-soft);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .print-table .is-right {
            text-align: right;
        }

        .print-totals {
            margin-top: 12px;
            margin-left: auto;
            width: 320px;
            max-width: 100%;
            border-collapse: collapse;
        }

        .print-totals td {
            border: 1px solid var(--print-border);
            padding: 8px 10px;
        }

        .print-totals td:first-child {
            background: var(--print-soft);
            font-weight: 700;
        }

        .print-footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid var(--print-border);
            color: var(--print-muted);
            font-size: 11px;
        }

        .print-footer div+div {
            margin-top: 2px;
        }

        @media print {
            @page {
                size: auto;
                margin: 12mm;
            }

            body {
                padding: 0;
            }

            .print-toolbar {
                display: none;
            }

            .print-document {
                border: 0;
                border-radius: 0;
                padding: 0;
            }

            .print-page {
                max-width: none;
            }
        }
    </style>
</head>

<body>
    <div class="print-page">
        <div class="print-toolbar no-print">
            <button type="button" class="print-toolbar-button" onclick="window.print()">
                Imprimir
            </button>

            <button type="button" class="print-toolbar-button" onclick="window.close()">
                Cerrar
            </button>
        </div>

        <div class="print-document">
            @include('print.partials.header')

            @yield('content')

            @include('print.partials.footer')
        </div>
    </div>
</body>

</html>
