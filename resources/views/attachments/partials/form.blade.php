{{-- FILE: resources/views/attachments/partials/form.blade.php | V3 --}}

@php
    use App\Support\Catalogs\AttachmentCatalog;
@endphp

<form method="POST" enctype="multipart/form-data"
    action="{{ isset($attachment) ? route('attachments.update', $attachment) : route('attachments.store') }}"
    class="form">
    @csrf
    @isset($attachment)
        @method('PUT')
    @endisset

    <input type="hidden" name="attachable_type" value="{{ $attachableType }}">
    <input type="hidden" name="attachable_id" value="{{ $attachableId }}">
    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    @if (!isset($attachment))
        <div class="form-group">
            <label class="form-label" for="file">Archivo</label>
            <input type="file" id="file" name="file" class="form-control @error('file') is-invalid @enderror"
                required>

            @error('file')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    @else
        <div class="form-group">
            <label class="form-label">Archivo</label>
            <input type="text" class="form-control" value="{{ $attachment->file_name }}" readonly>
        </div>
    @endif

    <div class="form-group">
        <label class="form-label" for="kind">Tipo</label>
        <select id="kind" name="kind" class="form-control @error('kind') is-invalid @enderror" required>
            @foreach (AttachmentCatalog::kindLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('kind', $attachment->kind ?? AttachmentCatalog::KIND_OTHER) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        @error('kind')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="description">Descripción</label>
        <input type="text" id="description" name="description"
            class="form-control @error('description') is-invalid @enderror"
            value="{{ old('description', $attachment->description ?? '') }}">

        @error('description')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            {{ isset($attachment) ? 'Guardar' : 'Subir' }}
        </button>

        <a href="{{ $returnTo }}" class="btn btn-secondary">
            Cancelar
        </a>
    </div>
</form>
