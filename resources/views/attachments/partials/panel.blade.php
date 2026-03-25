{{-- FILE: resources/views/attachments/partials/panel.blade.php | V2 --}}

@php
    $attachable = $attachable ?? null;
    $attachments = $attachments ?? collect();
    $title = $title ?? 'Adjuntos';
    $emptyMessage = $emptyMessage ?? 'No hay adjuntos cargados.';
    $showForm = $showForm ?? true;
    $createLabel = $createLabel ?? 'Agregar adjunto';
@endphp

<div class="detail-block">
    <div class="detail-block__header">
        <div class="detail-block__title-group">
            <h3>{{ $title }}</h3>
            @if ($attachments->count())
                <span class="detail-block__meta">{{ $attachments->count() }}</span>
            @endif
        </div>
    </div>

    <div class="detail-block__content">
        @if ($showForm && $attachable)
            <details class="attachment-create-box">
                <summary class="btn btn-secondary">
                    {{ $createLabel }}
                </summary>

                <div class="attachment-create-box__body">
                    @include('attachments.partials.upload-form', [
                        'attachable' => $attachable,
                    ])
                </div>
            </details>
        @endif

        <div class="section-spacer">
            @include('attachments.partials.list', [
                'attachments' => $attachments,
                'emptyMessage' => $emptyMessage,
            ])
        </div>
    </div>
</div>
