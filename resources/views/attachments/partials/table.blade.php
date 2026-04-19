{{-- FILE: resources/views/attachments/partials/table.blade.php | V7 --}}

@php
    use App\Support\Catalogs\AttachmentCatalog;

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
                    $kindLabel = AttachmentCatalog::kindLabel($attachment->kind);
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

                                    <div class="table-meta">
                                        {{ $extension ?: '—' }}
                                    </div>
                                </div>
                            @else
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
                                    <div>{{ $attachment->file_name }}</div>
                                    <div class="table-meta">{{ $extension ?: '—' }}</div>
                                </div>
                            @endcan
                        </div>
                    </td>

                    <td>{{ $kindLabel ?: '—' }}</td>
                    <td>{{ $sizeLabel }}</td>
                    <td>{{ $uploadedAt }}</td>
                    <td>{{ $attachment->description ?: '—' }}</td>

                    <td class="compact-actions-cell">
                        <div class="compact-actions">
                            @can('view', $attachment)
                                <x-button-tool :href="$downloadUrl" title="Descargar" label="Descargar">
                                    <x-icons.download />
                                </x-button-tool>
                            @endcan

                            @can('update', $attachment)
                                <x-button-tool :href="route('attachments.edit', $editRouteParams)" title="Editar" label="Editar">
                                    <x-icons.pencil />
                                </x-button-tool>
                            @endcan

                            @can('delete', $attachment)
                                <x-button-tool-submit :action="route('attachments.destroy', $destroyRouteParams)" method="DELETE" variant="danger" title="Eliminar"
                                    label="Eliminar" message="¿Eliminar adjunto?">
                                    <x-slot:fields>
                                        @if ($returnTo)
                                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        @endif
                                    </x-slot:fields>

                                    <x-icons.trash />
                                </x-button-tool-submit>
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
