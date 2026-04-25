{{-- FILE: resources/views/inventory/components/row-action-icon.blade.php | V1 --}}

@props([
    'icon' => null,
])

@switch($icon)
    @case('truck')
        <x-icons.truck />
    @break

    @case('plus')
        <x-icons.plus />
    @break

    @case('rotate-ccw')
        <x-icons.rotate-ccw />
    @break

    @case('check')
        <x-icons.check />
    @break

    @case('eye')
        <x-icons.eye />
    @break

    @default
        <x-icons.check />
@endswitch