{{-- FILE: resources/views/attachments/partials/upload-form.blade.php | V2 --}}

@php
    $attachable = $attachable ?? null;
    $attachableType = $attachable ? get_class($attachable) : $attachableType ?? null;
    $attachableId = $attachable?->getKey() ?? ($attachableId ?? null);

    $allowedKinds = $allowedKinds ?? [
        'photo' => 'Foto',
        'manual' => 'Manual',
        'evidence' => 'Evidencia',
        'support' => 'Soporte',
        'text' => 'Texto',
        'other' => 'Otro',
    ];

    $submitLabel = $submitLabel ?? 'Subir adjunto';
@endphp

@if ($attachableType && $attachableId)
    <form method="POST" action="{{ route('attachments.store') }}" enctype="multipart/form-data" class="form">
        @csrf

        <input type="hidden" name="attachable_type" value="{{ $attachableType }}">
        <input type="hidden" name="attachable_id" value="{{ $attachableId }}">

        <div class="form-grid form-grid--2">
            <div class="form-group">
                <label for="attachment-file" class="form-label">Archivo</label>
                <input id="attachment-file" type="file" name="file" class="form-control"
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" required>
                <div class="form-help">
                    Permitidos: JPG, JPEG, PNG, WEBP, PDF y TXT. Máximo 15 MB.
                </div>
                @error('file')
                    <div class="form-help">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="attachment-kind" class="form-label">Tipo</label>
                <select id="attachment-kind" name="kind" class="form-control">
                    @foreach ($allowedKinds as $value => $label)
                        <option value="{{ $value }}" @selected(old('kind') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('kind')
                    <div class="form-help">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="attachment-title" class="form-label">Título</label>
                <input id="attachment-title" type="text" name="title" class="form-control"
                    value="{{ old('title') }}" maxlength="255">
                @error('title')
                    <div class="form-help">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="attachment-category" class="form-label">Categoría</label>
                <input id="attachment-category" type="text" name="category" class="form-control"
                    value="{{ old('category') }}" maxlength="100">
                @error('category')
                    <div class="form-help">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group form-group--full">
                <label for="attachment-description" class="form-label">Descripción</label>
                <textarea id="attachment-description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="form-help">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                {{ $submitLabel }}
            </button>
        </div>
    </form>
@endif
