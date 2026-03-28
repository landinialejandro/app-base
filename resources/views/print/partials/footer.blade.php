{{-- FILE: resources/views/print/partials/footer.blade.php | V1 --}}
@php
    $tenant = app('tenant');
    $settings = is_array($tenant->settings ?? null) ? $tenant->settings : [];
    $printing = is_array($settings['printing'] ?? null) ? $settings['printing'] : [];

    $footerLines = $printing['footer_lines'] ?? [];
    $footerLines = is_array($footerLines)
        ? array_values(array_filter(array_map(fn($line) => trim((string) $line), $footerLines)))
        : [];
@endphp

<div class="print-footer">
    @if (!empty($footerLines))
        @foreach ($footerLines as $line)
            <div>{{ $line }}</div>
        @endforeach
    @else
        <div>Documento generado desde app-base.</div>
    @endif
</div>
