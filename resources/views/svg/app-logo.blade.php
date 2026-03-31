{{-- FILE: resources/views/svg/app-logo.blade.php | V6 --}}
<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"
    aria-label="app-base">
    <defs>
        <filter id="appLogoShadow" x="0" y="0" width="32" height="32" filterUnits="userSpaceOnUse">
            <feDropShadow dx="0" dy="1.2" stdDeviation="1" flood-opacity="0.18" />
        </filter>
    </defs>

    <g filter="url(#appLogoShadow)">
        <rect class="app-logo-active" x="3" y="3" width="12" height="12" rx="3.2" fill="currentColor" />
    </g>

    <rect x="18" y="4" width="10" height="10" rx="3" stroke="currentColor" stroke-width="1.6" />
    <rect x="4" y="18" width="10" height="10" rx="3" stroke="currentColor" stroke-width="1.6" />
    <rect x="18" y="18" width="10" height="10" rx="3" stroke="currentColor" stroke-width="1.6" />
</svg>
