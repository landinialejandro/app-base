{{-- FILE: resources/views/attachments/partials/form.blade.php | V2 --}}

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
            <label class="form-label">Archivo</label>
            <input type="file" name="file" class="form-control" required>
        </div>
    @else
        <div class="form-group">
            <label class="form-label">Archivo</label>
            <input type="text" class="form-control" value="{{ $attachment->file_name }}" readonly>
        </div>
    @endif

    <div class="form-group">
        <label class="form-label">Descripción</label>
        <input type="text" name="description" class="form-control"
            value="{{ old('description', $attachment->description ?? '') }}">
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
