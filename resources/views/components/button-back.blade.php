{{-- FILE:resources/views/components/button-back.blade.php --}}

@props(['href', 'title' => 'Volver', 'label' => 'Volver'])

<x-button-secondary :href="$href" class="btn-icon" :title="$title" :aria-label="$label">
    <x-icons.chevron-left />
</x-button-secondary>
