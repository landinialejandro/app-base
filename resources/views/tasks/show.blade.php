{{-- FILE: resources/views/tasks/show.blade.php | V17 --}}

@extends('layouts.app')

@section('title', $task->name)

@section('content')
    @php
        use App\Support\Catalogs\TaskCatalog;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Tasks\TaskSurfaceService;
        use Illuminate\Support\Carbon;

        $dueDateText = 'Sin vencimiento';

        if ($task->due_date) {
            $today = now()->startOfDay();
            $dueDate = Carbon::parse($task->due_date)->startOfDay();
            $diffInDays = $today->diffInDays($dueDate, false);

            $dueDateText = match (true) {
                $diffInDays === 0 => 'Vence hoy',
                $diffInDays === 1 => 'Vence mañana',
                $diffInDays === -1 => 'Venció ayer',
                $diffInDays > 1 => "Vence en {$diffInDays} días",
                $diffInDays < -1 => 'Venció hace ' . abs($diffInDays) . ' días',
                default => 'Sin vencimiento',
            };
        }

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $backUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            $task->project ? route('projects.show', $task->project) : route('tasks.index'),
        );

        $previousNode = NavigationTrail::previous($navigationTrail);

        $hostPack = app(TaskSurfaceService::class)->hostPack('tasks.show', $task, [
            'trailQuery' => $trailQuery,
        ]);

        $registry = app(ModuleSurfaceRegistry::class);

        $headerActions = collect($registry->slotFor('tasks.show', 'header_actions', $hostPack))
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();

        $summaryItems = collect($registry->slotFor('tasks.show', 'summary_items', $hostPack))
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();

        $detailItems = collect($registry->slotFor('tasks.show', 'detail_items', $hostPack))
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();

        $tabItems = collect($registry->slotFor('tasks.show', 'tab_panels', $hostPack))
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$task->name">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @can('update', $task)
                <x-button-edit :href="route('tasks.edit', ['task' => $task] + $trailQuery)" />
            @endcan

            @can('delete', $task)
                <x-button-delete :action="route('tasks.destroy', ['task' => $task] + $trailQuery)" message="¿Eliminar tarea?" />
            @endcan

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="task-more-detail">
            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-show-summary-item label="Asignado a">
                {{ $task->assignedUser?->name ?? 'Sin asignar' }}
            </x-show-summary-item>

            <x-show-summary-item label="Estado operativo" help="{{ $dueDateText }}">
                <div class="summary-badge-stack">
                    <span class="status-badge {{ TaskCatalog::priorityBadgeClass($task->priority) }}">
                        Prioridad: {{ TaskCatalog::priorityLabel($task->priority) }}
                    </span>

                    <span class="status-badge {{ TaskCatalog::badgeClass($task->status) }}">
                        Estado: {{ TaskCatalog::label($task->status) }}
                    </span>
                </div>
            </x-show-summary-item>

            <x-slot:details>
                @foreach ($detailItems as $detailItem)
                    <x-show-summary-item-detail-block :label="$detailItem['label'] ?? 'Relacionado'">
                        @include($detailItem['view'], $detailItem['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

                <x-show-summary-item-detail-block label="Vencimiento">
                    {{ $task->due_date?->format('d/m/Y') ?? 'Sin fecha' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $task->description ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        @if ($tabItems->isNotEmpty())
            <div class="tabs" data-tabs">
                <x-tab-toolbar label="Secciones de la tarea">
                    <x-slot:tabs>
                        <x-horizontal-scroll label="Secciones de la tarea">
                            @foreach ($tabItems as $tabItem)
                                <button type="button" class="tabs-link {{ $loop->first ? 'is-active' : '' }}"
                                    data-tab-link="{{ $tabItem['key'] }}" role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $tabItem['label'] ?? $tabItem['key'] }}

                                    @if (array_key_exists('count', $tabItem) && (int) $tabItem['count'] > 0)
                                        ({{ $tabItem['count'] }})
                                    @endif
                                </button>
                            @endforeach
                        </x-horizontal-scroll>
                    </x-slot:tabs>
                </x-tab-toolbar>

                @foreach ($tabItems as $tabItem)
                    <section class="tab-panel {{ $loop->first ? 'is-active' : '' }}"
                        data-tab-panel="{{ $tabItem['key'] }}" @unless ($loop->first) hidden @endunless>
                        <div class="tab-panel-stack">
                            @include($tabItem['view'], $tabItem['data'] ?? [])
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-page>
@endsection
