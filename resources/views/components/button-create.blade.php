{{-- FILE:resources/views/components/button-create.blade.php --}}
@props(['href', 'label' => 'Nuevo'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-success']) }}>
    <x-icons.plus? />
    <span>{{ $label }}</span>
</a>
