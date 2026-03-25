{{-- FILE: resources/views/attachments/partials/viewer-modal.blade.php | V1 --}}

@php
    $attachments = $attachments ?? collect();
    $modalId = $modalId ?? 'attachments-viewer-modal';
@endphp

<x-modal :id="$modalId" title="Visor de adjuntos" size="xl">
    <div class="attachment-viewer" data-attachment-viewer>
        <div class="attachment-viewer__toolbar">
            <button type="button" class="btn btn-secondary btn-icon" data-action="app-attachment-viewer-prev"
                data-modal-target="#{{ $modalId }}" title="Anterior" aria-label="Adjunto anterior">
                <x-icons.chevron-left />
            </button>

            <div class="attachment-viewer__heading">
                <strong data-attachment-viewer-title>Adjunto</strong>
                <div class="table-meta" data-attachment-viewer-meta>—</div>
            </div>

            <button type="button" class="btn btn-secondary btn-icon" data-action="app-attachment-viewer-next"
                data-modal-target="#{{ $modalId }}" title="Siguiente" aria-label="Adjunto siguiente">
                <x-icons.chevron-right />
            </button>
        </div>

        <div class="attachment-viewer__body">
            <div class="attachment-viewer__image-wrap" data-attachment-viewer-image-wrap hidden>
                <img src="" alt="" class="attachment-viewer__image" data-attachment-viewer-image>
            </div>

            <div class="attachment-viewer__fallback" data-attachment-viewer-fallback hidden>
                <p data-attachment-viewer-fallback-text>
                    Este archivo no tiene vista enriquecida en esta etapa.
                </p>

                <div class="form-actions">
                    <a href="#" class="btn btn-primary" data-attachment-viewer-preview-link target="_blank"
                        rel="noreferrer">
                        Abrir preview
                    </a>
                    <a href="#" class="btn btn-secondary" data-attachment-viewer-download-link>
                        Descargar
                    </a>
                </div>
            </div>
        </div>

        <div class="attachment-viewer__items" hidden>
            @foreach ($attachments as $attachment)
                <div data-attachment-viewer-item data-attachment-id="{{ $attachment->id }}"
                    data-name="{{ $attachment->display_name }}"
                    data-meta="{{ $attachment->original_name }}{{ $attachment->extension_label !== '' ? ' · ' . $attachment->extension_label : '' }} · {{ $attachment->size_label }}"
                    data-is-image="{{ $attachment->is_image ? '1' : '0' }}"
                    data-preview-url="{{ route('attachments.preview', $attachment) }}"
                    data-download-url="{{ route('attachments.download', $attachment) }}">
                </div>
            @endforeach
        </div>
    </div>
</x-modal>
