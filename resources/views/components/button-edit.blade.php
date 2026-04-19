{{-- resources/views/components/boton-editar.blade.php --}}
@props(['href', 'label' => 'Editar'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-primary']) }}>
    <x-icons.pencil />
    <span>{{ $label }}</span>
</a>
