{{-- FILE: resources/views/components/layout/footer.blade.php | V2 --}}

<footer class="app-footer">
    <div class="container app-footer-inner">
        <div class="app-footer-main">
            <span class="app-footer-mark" aria-hidden="true">
                @include('svg.app-logo')
            </span>

            <span class="app-footer-brand">app-base</span>
        </div>

        @if (!empty($appFooterVersionLabel))
            <span class="app-footer-version">{{ $appFooterVersionLabel }}</span>
        @endif
    </div>
</footer>