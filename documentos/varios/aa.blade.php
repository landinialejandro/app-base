<x-page>
    <x-breadcrumb :items="$breadcrumbItems" />

    <x-page-header :title="$pageTitle">
        {{-- loop: header_actions --}}
    </x-page-header>

    <x-show-summary details-id="{{ $detailsId }}">
        {{-- loop: summary_items --}}

        <x-slot:details>
            {{-- loop: detail_items --}}
        </x-slot:details>
    </x-show-summary>

    {{-- coordinated tab area --}}
    @if ($tabItems->isNotEmpty())
        <div class="tabs" data-tabs>
            <x-tab-toolbar :label="$tabsLabel">
                <x-slot:tabs>
                    <x-horizontal-scroll :label="$tabsLabel">
                        {{-- loop: tab_items as tab buttons --}}
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            {{-- loop: tab_items as tab panels --}}
        </div>
    @endif
</x-page>
