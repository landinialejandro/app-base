{{-- FILE: resources/views/attachments/partials/list.blade.php | V2 --}}

@php
    $attachments = $attachments ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay adjuntos cargados.';
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
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attachments as $attachment)
                    @php
                        $extension = strtoupper((string) ($attachment->extension ?? ''));
                        $kind = $attachment->kind ?? 'other';
                        $kindLabel = match ($kind) {
                            'photo' => 'Foto',
                            'manual' => 'Manual',
                            'evidence' => 'Evidencia',
                            'support' => 'Soporte',
                            'text' => 'Texto',
                            default => 'Otro',
                        };

                        $sizeBytes = (int) ($attachment->size_bytes ?? 0);
                        $sizeLabel = $sizeBytes > 0 ? number_format($sizeBytes / 1024, 2, ',', '.') . ' KB' : '—';

                        $createdAt = $attachment->created_at;
                        $userLabel = $attachment->uploadedBy?->name ?: '—';
                    @endphp

                    <tr>
                        <td>
                            <div>
                                <strong>{{ $attachment->display_name }}</strong>
                            </div>

                            <div class="table-meta">
                                {{ $attachment->original_name }}
                                @if ($extension !== '')
                                    · {{ $extension }}
                                @endif
                            </div>

                            @if (!empty($attachment->description))
                                <div class="table-meta">
                                    {{ $attachment->description }}
                                </div>
                            @endif
                        </td>

                        <td>{{ $kindLabel }}</td>

                        <td>{{ $sizeLabel }}</td>

                        <td>
                            @if ($createdAt)
                                {{ $createdAt->format('d/m/Y H:i') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>{{ $userLabel }}</td>

                        <td>
                            <div class="compact-actions compact-actions--end">
                                <a href="{{ route('attachments.preview', $attachment) }}" class="btn btn-sm"
                                    target="_blank" rel="noreferrer" title="Ver adjunto">
                                    Ver
                                </a>

                                <a href="{{ route('attachments.download', $attachment) }}" class="btn btn-sm"
                                    title="Descargar adjunto">
                                    Descargar
                                </a>

                                <form method="POST" action="{{ route('attachments.destroy', $attachment) }}"
                                    class="inline-form" data-action="app-confirm-submit"
                                    data-confirm-message="¿Eliminar este adjunto?">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar adjunto">
                                        Eliminar
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
