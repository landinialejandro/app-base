{{-- FILE: resources/views/components/tab-toolbar.blade.php | V4 --}}

@props([
    'label' => null,
])

<x-card class="tab-toolbar-card">
    <div class="card-header-actions tab-toolbar">
        <div class="tab-toolbar-nav"
            @if ($label) role="tablist" aria-label="{{ $label }}" @endif>
            {{ $tabs ?? '' }}
        </div>

        @if (trim((string) ($actions ?? '')) !== '')
            <div class="tab-toolbar-actions">
                {{ $actions }}
            </div>
        @endif
    </div>
</x-card>
