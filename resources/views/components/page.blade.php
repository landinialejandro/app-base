{{-- FILE: resources/views/components/page.blade.php | v2 --}}

<div {{ $attributes->merge(['class' => 'page']) }}>
    {{ $slot }}
</div>