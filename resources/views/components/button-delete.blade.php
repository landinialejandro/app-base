{{-- resources/views/components/boton-eliminar.blade.php --}}
@props(['action', 'label' => 'Eliminar', 'message' => '¿Eliminar registro?', 'method' => 'DELETE'])

<form method="POST" action="{{ $action }}" class="inline-form" data-action="app-confirm-submit"
    data-confirm-message="{{ $message }}">
    @csrf
    @method($method)

    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-danger']) }}>
        <x-icons.trash />
        <span>{{ $label }}</span>
    </button>
</form>
