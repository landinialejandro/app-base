{{-- resources/views/filament/super/pages/dashboard.blade.php --}}
<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns() ?? [
            'md' => 2,
            'xl' => 3,
        ]"
    />
</x-filament-panels::page>