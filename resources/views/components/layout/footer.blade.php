{{-- FILE: resources/views/components/layout/footer.blade.php | V1 --}}

<footer class="app-footer">
    <div class="container app-footer-inner">
        <span class="app-footer-brand">app-base</span>

        @if (!empty($appFooterVersionLabel))
            <span class="app-footer-separator">·</span>
            <span class="app-footer-version">{{ $appFooterVersionLabel }}</span>
        @endif
    </div>
</footer>
