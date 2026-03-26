{{-- FILE: resources/views/attachments/partials/table.blade.php | V2 --}}

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
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('attachments.download', $attachment) }}">
                            {{ $attachment->file_name }}
                        </a>
                    </td>

                    <td>{{ $attachment->description ?: '—' }}</td>

                    <td class="compact-actions-cell">
                        <div class="compact-actions">
                            <a href="{{ route('attachments.edit', $editRouteParams) }}" title="Editar" aria-label="Editar">
                                <x-icons.pencil />
                            </a>

                            <form method="POST"
                                action="{{ route('attachments.destroy', ['attachment' => $attachment] + $trailQuery) }}"
                                class="inline-form" data-action="app-confirm-submit"
                                data-confirm-message="¿Eliminar adjunto?">
                                @csrf
                                @method('DELETE')

                                <button type="submit" title="Eliminar" aria-label="Eliminar">
                                    <x-icons.trash />
                                </button>
                            </form>
                        </div>
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
