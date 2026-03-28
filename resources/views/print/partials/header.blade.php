{{-- FILE: resources/views/print/partials/header.blade.php | V1 --}}
@php
    $tenant = app('tenant');
    $settings = is_array($tenant->settings ?? null) ? $tenant->settings : [];

    $printing = is_array($settings['printing'] ?? null) ? $settings['printing'] : [];

    $companyName = trim((string) ($printing['company_name'] ?? ($tenant->name ?? '')));
    $headerLines = $printing['header_lines'] ?? [];
    $headerLines = is_array($headerLines)
        ? array_values(array_filter(array_map(fn($line) => trim((string) $line), $headerLines)))
        : [];

    $logoPath = trim((string) ($printing['logo_path'] ?? ''));
@endphp

<div class="print-header">
    <div>
        <h1 class="print-company-name">
            {{ $companyName !== '' ? $companyName : 'Empresa' }}
        </h1>

        @if (!empty($headerLines))
            <div class="print-company-meta">
                @foreach ($headerLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($logoPath !== '')
        <div class="print-logo-wrap">
            <img src="{{ asset($logoPath) }}" alt="Logo de empresa" class="print-logo">
        </div>
    @endif
</div>
