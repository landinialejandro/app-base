{{-- FILE: resources/views/attachments/partials/table.blade.php | V5 --}}

@php
    $trailQuery = $trailQuery ?? [];
    $returnTo = $returnTo ?? null;
@endphp

<div class="table-wrap list-scroll">
    <table class="table">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Tipo</th>
                <th>Tamaño</th>
                <th>Fecha</th>
                <th>Descripción</th>
                <th class="compact-actions-cell"></th>
            </tr>
        </thead>

        <tbody>
            @forelse($attachments as $attachment)
                @php
                    $editRouteParams = ['attachment' => $attachment] + $trailQuery;
                    if ($returnTo) {
                        $editRouteParams['return_to'] = $returnTo;
                    }

                    $destroyRouteParams = ['attachment' => $attachment] + $trailQuery;

                    $extension = strtoupper((string) ($attachment->extension ?: ''));
                    $sizeLabel =
                        $attachment->size_bytes !== null
                            ? number_format($attachment->size_bytes / 1024, 1, ',', '.') . ' KB'
                            : '—';
                    $uploadedAt = $attachment->created_at?->format('d/m/Y H:i') ?: '—';

                    $previewUrl = route('attachments.preview', $attachment);
                    $downloadUrl = route('attachments.download', $attachment);
                @endphp

                <tr>
                    <td>
                        <div class="attachment-file-cell">
                            @can('view', $attachment)
                                <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="attachment-file-thumb-link"
                                    aria-label="Abrir vista previa de {{ $attachment->file_name }}">
                                    @if ($attachment->isImage())
                                        <img src="{{ $previewUrl }}" alt="{{ $attachment->file_name }}"
                                            class="attachment-thumb">
                                    @else
                                        <span class="attachment-thumb attachment-thumb--placeholder">
                                            {{ $extension ?: 'FILE' }}
                                        </span>
                                    @endif
                                </a>

                                <div class="attachment-file-meta">
                                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                                        class="attachment-file-link">
                                        {{ $attachment->file_name }}
                                    </a>
                                </div>
                            @else
                                <div class="attachment-file-cell">
                                    @if ($attachment->isImage())
                                        <span class="attachment-thumb-wrap">
                                            <span class="attachment-thumb attachment-thumb--placeholder">IMG</span>
                                        </span>
                                    @else
                                        <span class="attachment-thumb attachment-thumb--placeholder">
                                            {{ $extension ?: 'FILE' }}
                                        </span>
                                    @endif

                                    <div class="attachment-file-meta">
                                        {{ $attachment->file_name }}
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </td>

                    <td>{{ $extension ?: '—' }}</td>
                    <td>{{ $sizeLabel }}</td>
                    <td>{{ $uploadedAt }}</td>
                    <td>{{ $attachment->description ?: '—' }}</td>

                    <td class="compact-actions-cell">
                        <div class="compact-actions">
                            @can('view', $attachment)
                                <a href="{{ $downloadUrl }}" class="btn btn-secondary btn-icon" title="Descargar"
                                    aria-label="Descargar">
                                    <x-icons.download />
                                </a>
                            @endcan

                            @can('update', $attachment)
                                <a href="{{ route('attachments.edit', $editRouteParams) }}"
                                    class="btn btn-secondary btn-icon" title="Editar" aria-label="Editar">
                                    <x-icons.pencil />
                                </a>
                            @endcan

                            @can('delete', $attachment)
                                <form method="POST" action="{{ route('attachments.destroy', $destroyRouteParams) }}"
                                    class="inline-form" data-action="app-confirm-submit"
                                    data-confirm-message="¿Eliminar adjunto?">
                                    @csrf
                                    @method('DELETE')

                                    @if ($returnTo)
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                    @endif

                                    <button type="submit" class="btn btn-danger btn-icon" title="Eliminar"
                                        aria-label="Eliminar">
                                        <x-icons.trash />
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Sin adjuntos cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
