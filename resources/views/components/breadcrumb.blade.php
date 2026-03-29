{{-- FILE: resources/views/components/breadcrumb.blade.php | V2 --}}

@props([
    'items' => [],
])

@if (!empty($items))
    <nav {{ $attributes->class(['breadcrumb']) }} aria-label="Breadcrumb">
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
