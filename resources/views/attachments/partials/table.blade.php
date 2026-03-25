{{-- FILE: resources/views/attachments/partials/table.blade.php | V2 --}}

@php
    $attachments = $attachments ?? collect();
    $attachable = $attachable ?? null;
    $emptyMessage = $emptyMessage ?? 'No hay adjuntos cargados.';
    $viewerModalId = $viewerModalId ?? 'attachments-viewer-modal';
    $viewerIds = $viewerIds ?? $attachments->pluck('id')->all();
    $returnTo = $returnTo ?? url()->current();
    $renderEditModals = $renderEditModals ?? true;
@endphp

<div class="table-wrap list-scroll">
    @if ($attachments->isEmpty())
        <div class="empty-state">
            <p>{{ $emptyMessage }}</p>
        </div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tipo</th>
                    <th>Tamaño</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attachments->values() as $index => $attachment)
                    @php
                        $editModalId = 'attachment-edit-modal-' . $attachment->id;
                    @endphp

                    <tr>
                        <td>
                            <div class="attachment-file-cell">
                                <div class="attachment-file-cell__thumb">
                                    @if ($attachment->is_image)
                                        <img src="{{ route('attachments.preview', $attachment) }}"
                                            alt="{{ $attachment->display_name }}" class="attachment-thumb">
                                    @else
                                        <span class="attachment-file-badge">
                                            {{ $attachment->extension_label !== '' ? $attachment->extension_label : 'FILE' }}
                                        </span>
                                    @endif
                                </div>

                                <div class="attachment-file-cell__content">
                                    <a href="{{ route('attachments.preview', $attachment) }}"
                                        class="attachment-primary-link" data-action="app-attachment-viewer-open"
                                        data-modal-target="#{{ $viewerModalId }}"
                                        data-attachment-id="{{ $attachment->id }}"
                                        data-viewer-index="{{ $index }}"
                                        data-viewer-ids="{{ implode(',', $viewerIds) }}">
                                        {{ $attachment->display_name }}
                                    </a>

                                    <div class="table-meta">
                                        {{ $attachment->original_name }}
                                        @if ($attachment->extension_label !== '')
                                            · {{ $attachment->extension_label }}
                                        @endif
                                    </div>

                                    @if (!empty($attachment->description))
                                        <div class="table-meta">
                                            {{ $attachment->description }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>
                            <div>{{ $attachment->kind_label }}</div>
                            <div class="table-meta">{{ $attachment->category_label }}</div>
                        </td>

                        <td>{{ $attachment->size_label }}</td>

                        <td>
                            @if ($attachment->created_at)
                                {{ $attachment->created_at->format('d/m/Y H:i') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>{{ $attachment->uploadedBy?->name ?: '—' }}</td>

                        <td class="compact-actions-cell">
                            <div class="compact-actions compact-actions--end">
                                <a href="{{ route('attachments.download', $attachment) }}"
                                    class="btn btn-secondary btn-icon" title="Descargar adjunto"
                                    aria-label="Descargar adjunto">
                                    <x-icons.download />
                                </a>

                                <button type="button" class="btn btn-secondary btn-icon" data-action="app-modal-open"
                                    data-modal-target="#{{ $editModalId }}" title="Editar adjunto"
                                    aria-label="Editar adjunto">
                                    <x-icons.pencil />
                                </button>

                                <form method="POST" action="{{ route('attachments.destroy', $attachment) }}"
                                    class="inline-form" data-action="app-confirm-submit"
                                    data-confirm-message="¿Eliminar este adjunto?">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="{{ $returnTo }}">

                                    <button type="submit" class="btn btn-danger btn-icon" title="Eliminar adjunto"
                                        aria-label="Eliminar adjunto">
                                        <x-icons.trash />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@if ($renderEditModals)
    @foreach ($attachments as $attachment)
        @include('attachments.partials.edit-modal', [
            'attachment' => $attachment,
            'attachable' => $attachable,
            'modalId' => 'attachment-edit-modal-' . $attachment->id,
            'returnTo' => $returnTo,
        ])
    @endforeach
@endif
