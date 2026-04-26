{{-- FILE: resources/views/components/line-operation-modal.blade.php | V1 --}}

@props([
    'modalId',
    'title',
    'position' => '—',
    'action' => '#',
    'method' => 'POST',
    'hiddenFields' => [],
    'productName' => 'Producto',
    'summaryItems' => [],
    'quantityLabel' => 'Cantidad',
    'quantityDefault' => '0.01',
    'quantityMax' => null,
    'quantityMin' => '0.01',
    'quantityStep' => '0.01',
    'submitVariant' => 'primary',
    'submitLabel' => null,
    'helperText' => null,
    'useStepper' => true,
])

@php
    $submitFormId = $modalId . '-form';
    $quantityInputId = $modalId . '-quantity';
    $notesInputId = $modalId . '-notes';

    $method = strtoupper((string) $method);
    $hiddenFields = is_array($hiddenFields) ? $hiddenFields : [];
    $summaryItems = collect($summaryItems ?? [])->values();

    $submitLabel = $submitLabel ?: 'Confirmar ' . strtolower($title) . ' #' . $position;
@endphp

<x-modal :id="$modalId" :title="$title . ' #' . $position" size="md">
    <x-slot:headerActions>
        <x-button-tool-button
            type="submit"
            :form="$submitFormId"
            :variant="$submitVariant"
            :title="$submitLabel"
            :label="$submitLabel"
        >
            <x-icons.check />
        </x-button-tool-button>
    </x-slot:headerActions>

    <form
        id="{{ $submitFormId }}"
        action="{{ $action }}"
        method="POST"
        class="form"
    >
        @csrf

        @if ($method !== 'POST')
            @method($method)
        @endif

        @foreach ($hiddenFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        <div class="form-group">
            <label class="form-label">Producto</label>
            <input type="text" class="form-control" value="{{ $productName }}" disabled>
        </div>

        @if ($summaryItems->isNotEmpty())
            <div class="summary-inline-grid">
                @foreach ($summaryItems as $summaryItem)
                    <div class="summary-inline-card">
                        <div class="summary-inline-label">{{ $summaryItem['label'] ?? 'Dato' }}</div>
                        <div class="summary-inline-value">{{ $summaryItem['value'] ?? '—' }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="form-group">
            <label for="{{ $quantityInputId }}" class="form-label">{{ $quantityLabel }}</label>

            @if ($useStepper)
                <div class="app-stepper">
                    <button
                        type="button"
                        class="app-stepper__button btn btn-secondary btn-icon"
                        data-action="app-step-number"
                        data-step-target="#{{ $quantityInputId }}"
                        data-step-direction="down"
                        data-step-amount="{{ $quantityStep }}">
                        <x-icons.minus />
                    </button>

                    <div class="app-stepper__field">
                        <input
                            id="{{ $quantityInputId }}"
                            name="quantity"
                            type="number"
                            step="{{ $quantityStep }}"
                            min="{{ $quantityMin }}"
                            @if ($quantityMax !== null) max="{{ $quantityMax }}" @endif
                            class="form-control app-stepper__input"
                            value="{{ old('quantity', $quantityDefault) }}"
                            required
                            inputmode="decimal"
                            autocomplete="off"
                            data-modal-autofocus
                        >
                    </div>

                    <button
                        type="button"
                        class="app-stepper__button btn btn-secondary btn-icon"
                        data-action="app-step-number"
                        data-step-target="#{{ $quantityInputId }}"
                        data-step-direction="up"
                        data-step-amount="{{ $quantityStep }}">
                        <x-icons.plus />
                    </button>
                </div>
            @else
                <input
                    id="{{ $quantityInputId }}"
                    name="quantity"
                    type="number"
                    step="{{ $quantityStep }}"
                    min="{{ $quantityMin }}"
                    @if ($quantityMax !== null) max="{{ $quantityMax }}" @endif
                    class="form-control"
                    value="{{ old('quantity', $quantityDefault) }}"
                    required
                    inputmode="decimal"
                    autocomplete="off"
                    data-modal-autofocus
                >
            @endif

            @error('quantity')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror

            @if ($helperText)
                <div class="form-help">{{ $helperText }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="{{ $notesInputId }}" class="form-label">Notas</label>

            <input
                id="{{ $notesInputId }}"
                name="notes"
                type="text"
                class="form-control"
                value="{{ old('notes') }}"
                placeholder="Opcional"
            >

            @error('notes')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    </form>
</x-modal>