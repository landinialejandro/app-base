{{-- FILE: resources/views/attachments/partials/table.blade.php | V3 --}}

@php
    $trailQuery = $trailQuery ?? [];
    $returnTo = $returnTo ?? null;
@endphp

<div class="table-wrap list-scroll">
    <table class="table">
        <thead>
            <tr>
                <th>Archivo</th>
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

                    <td>{{ $attachment->description ?: '—' }}</td>

                    <td class="compact-actions-cell">
                        @can('update', $attachment)
                            <div class="compact-actions">
                                <a href="{{ route('attachments.edit', $editRouteParams) }}" class="btn btn-secondary btn-icon"
                                    title="Editar" aria-label="Editar">
                                    <x-icons.pencil />
                                </a>

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
                            </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Sin adjuntos</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
