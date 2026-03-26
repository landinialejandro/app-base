{{-- FILE: resources/views/attachments/partials/form.blade.php | V4 --}}

@php
    use App\Support\Attachments\AttachmentCategory;
    use App\Support\Attachments\AttachmentKind;

    $mode = $mode ?? 'create';
    $attachment = $attachment ?? null;
    $attachable = $attachable ?? null;
    $action =
        $action ??
        ($mode === 'edit' && $attachment ? route('attachments.update', $attachment) : route('attachments.store'));
    $method = $method ?? ($mode === 'edit' ? 'PUT' : 'POST');
    $submitLabel = $submitLabel ?? ($mode === 'edit' ? 'Guardar cambios' : 'Subir adjunto');
    $cancelUrl = $cancelUrl ?? null;
    $returnTo = $returnTo ?? url()->current();

    $attachableType = $attachable ? get_class($attachable) : null;
    $attachableId = $attachable?->getKey();

    $formKey =
        $mode === 'edit'
            ? 'attachment-edit-' . ($attachment?->id ?? 'x')
            : 'attachment-create-' . ($attachableType ?? 'x') . '-' . ($attachableId ?? 'x');

    $oldMode = old('attachment_form_mode');
    $oldKey = old('attachment_form_key');

    $useOldInput = $oldMode === $mode && $oldKey === $formKey;

    $kindValue = $useOldInput ? old('kind') : $attachment?->kind ?? AttachmentKind::OTHER;

    $kindValue = $kindValue ?: AttachmentKind::OTHER;

    $titleValue = $useOldInput ? old('title') : $attachment?->title ?? null;

    $categoryValue = $useOldInput
        ? old('category')
        : $attachment?->category ?? AttachmentKind::defaultCategory($kindValue);

    $categoryValue = $categoryValue ?: AttachmentKind::defaultCategory($kindValue);

    $descriptionValue = $useOldInput ? old('description') : $attachment?->description ?? null;

    $fileError = $useOldInput ? $errors->first('file') : null;
    $kindError = $useOldInput ? $errors->first('kind') : null;
    $categoryError = $useOldInput ? $errors->first('category') : null;
    $titleError = $useOldInput ? $errors->first('title') : null;
    $descriptionError = $useOldInput ? $errors->first('description') : null;

    $isCreate = $mode === 'create';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="form" autocomplete="off"
    @if ($isCreate) data-attachment-create-form="1" @endif>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="return_to" value="{{ $returnTo }}">
    <input type="hidden" name="attachment_form_mode" value="{{ $mode }}">
    <input type="hidden" name="attachment_form_key" value="{{ $formKey }}">

    @if ($mode === 'create' && $attachableType && $attachableId)
        <input type="hidden" name="attachable_type" value="{{ $attachableType }}">
        <input type="hidden" name="attachable_id" value="{{ $attachableId }}">
    @endif

    <div class="form-grid form-grid--2">
        @if ($mode === 'create')
            <div class="form-group form-group--full">
                <label for="attachment-file-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                    class="form-label">
                    Archivo
                </label>
                <input id="attachment-file-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                    type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" required
                    autocomplete="off">
                <div class="form-help">
                    Permitidos: JPG, JPEG, PNG, WEBP, PDF y TXT. Máximo 15 MB.
                </div>
                @if ($fileError)
                    <div class="form-help">{{ $fileError }}</div>
                @endif
            </div>
        @endif

        <div class="form-group">
            <label for="attachment-kind-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                class="form-label">
                Tipo
            </label>
            <select id="attachment-kind-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                name="kind" class="form-control" autocomplete="off">
                @foreach (AttachmentKind::options() as $value => $label)
                    <option value="{{ $value }}" @selected($kindValue === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($kindError)
                <div class="form-help">{{ $kindError }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="attachment-category-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                class="form-label">
                Categoría
            </label>
            <select id="attachment-category-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                name="category" class="form-control" autocomplete="off">
                @foreach (AttachmentCategory::options() as $value => $label)
                    <option value="{{ $value }}" @selected($categoryValue === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($categoryError)
                <div class="form-help">{{ $categoryError }}</div>
            @endif
        </div>

        <div class="form-group form-group--full">
            <label for="attachment-title-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                class="form-label">
                Título
            </label>
            <input id="attachment-title-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                type="text" name="title" class="form-control" value="{{ $titleValue }}" maxlength="255"
                autocomplete="off">
            @if ($titleError)
                <div class="form-help">{{ $titleError }}</div>
            @endif
        </div>

        <div class="form-group form-group--full">
            <label for="attachment-description-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                class="form-label">
                Descripción
            </label>
            <textarea id="attachment-description-{{ $mode }}-{{ $attachableId ?? ($attachment?->id ?? 'x') }}"
                name="description" class="form-control" rows="4" autocomplete="off">{{ $descriptionValue }}</textarea>
            @if ($descriptionError)
                <div class="form-help">{{ $descriptionError }}</div>
            @endif
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>

        @if ($cancelUrl)
            <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
        @endif
    </div>
</form>
