{{-- FILE: resources/views/components/dev-component-version.blade.php | V1 --}}

@props(['name', 'version', 'align' => 'right'])

@if (config('app.debug'))
    <div
        style="
        text-align:{{ $align }};
        font-size:12px;
        color:#9ca3af;
        opacity:.65;
        margin-top:4px;
        pointer-events:none;
        user-select:none;
    ">
        component: {{ $name }} · {{ $version }}
    </div>
@endif
