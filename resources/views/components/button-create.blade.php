{{-- FILE:resources/views/components/button-create.blade.php --}}
@props(['href', 'label' => 'Nuevo'])

<x-button-success :href="$href" {{ $attributes }}>
    <x-icons.plus />
    <span>{{ $label }}</span>
</x-button-success>
