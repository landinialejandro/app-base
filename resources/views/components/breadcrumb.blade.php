@props([
    'items' => [],
])

@if (!empty($items))
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
            @foreach ($items as $item)
                <li class="breadcrumb-item">
                    @if (!$loop->last && !empty($item['url']))
                        <a href="{{ $item['url'] }}" class="breadcrumb-link">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="breadcrumb-current">
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif