{{-- FILE: resources/views/docs/partials/document-nav.blade.php | V4 --}}
<x-card>
    <h2 style="margin-top: 0;">Documentos</h2>

    <div style="max-height: 45vh; overflow-y: auto; padding-right: .25rem;">
        <ul style="margin: 0; padding-left: 1rem;">
            @foreach ($documents as $item)
                <li>
                    @if ($item->slug === $currentDocument->slug)
                        <strong>{{ $item->title }}</strong>
                    @else
                        <a href="{{ route('docs.show', ['slug' => $item->slug]) }}">
                            {{ $item->title }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</x-card>
