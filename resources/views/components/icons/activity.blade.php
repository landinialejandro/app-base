{{-- FILE: resources/views/components/icons/activity.blade.php | V1 --}}

<svg
    {{ $attributes->merge([
        'class' => 'icon icon-activity',
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'aria-hidden' => 'true',
    ]) }}
    xmlns="http://www.w3.org/2000/svg"
>
    <path
        d="M4 19V5"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
    />
    <path
        d="M4 7H8.5C9.7 7 10.6 7.9 10.6 9.1V14.9C10.6 16.1 11.5 17 12.7 17H20"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
    />
    <circle
        cx="8"
        cy="7"
        r="1.6"
        fill="currentColor"
    />
    <circle
        cx="16"
        cy="17"
        r="1.6"
        fill="currentColor"
    />
</svg>