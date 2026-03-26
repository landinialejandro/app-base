{{-- FILE: resources/views/attachments/partials/table.blade.php | V4 --}}

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

                    $extension = strtoupper((string) $attachment->extension ?: '—');
                    $sizeLabel =
                        $attachment->size_bytes !== null
                            ? number_format($attachment->size_bytes / 1024, 1, ',', '.') . ' KB'
                            : '—';
                    $uploadedAt = $attachment->created_at?->format('d/m/Y H:i') ?: '—';
                @endphp

                <tr>
                    <td>
                        @can('view', $attachment)
                            <a href="{{ route('attachments.download', $attachment) }}">
                                {{ $attachment->file_name }}
                            </a>
                        @else
                            {{ $attachment->file_name }}
                        @endcan
                    </td>

                    <td>{{ $extension }}</td>

                    <td>{{ $sizeLabel }}</td>

                    <td>{{ $uploadedAt }}</td>

                    <td>{{ $attachment->description ?: '—' }}</td>

                    <td class="compact-actions-cell">
                        <div class="compact-actions">
                            @can('update', $attachment)
                                <a href="{{ route('attachments.edit', $editRouteParams) }}" class="btn btn-secondary btn-icon"
                                    title="Editar" aria-label="Editar">
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
