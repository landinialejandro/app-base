{{-- FILE: resources/views/tasks/show.blade.php | V14 --}}

@extends('layouts.app')

@section('title', $task->name)

@section('content')
    @php
        use App\Support\Auth\TenantModuleAccess;
        use App\Support\Catalogs\ModuleCatalog;
        use App\Support\Catalogs\TaskCatalog;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Orders\OrderLinkedAction;
        use App\Support\Parties\PartyLinkedAction;
        use Illuminate\Support\Carbon;

        $attachments = $task->attachments ?? collect();
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

        $tenant = app('tenant');
        $user = auth()->user();

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);

        $backUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            $task->project ? route('projects.show', $task->project) : route('tasks.index'),
        );

        $previousNode = NavigationTrail::previous($navigationTrail);

        $backLabel = match ($previousNode['key'] ?? null) {
            'projects.show' => 'Volver al proyecto',
            'orders.show' => 'Volver a la orden',
            default => 'Volver',
        };

        $supportsProjectsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PROJECTS, $tenant);
        $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);

        $canViewProject = $supportsProjectsModule && $task->project && $user && $user->can('view', $task->project);

        $partyAction = PartyLinkedAction::forParty($task->party, $trailQuery, 'Contacto');

        $linkedOrderAction = OrderLinkedAction::forTask(
            $task,
            $trailQuery,
            (bool) ($user && $user->can('update', $task)),
        );
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$task->name">
            @can('update', $task)
                <x-button-edit :href="route('tasks.edit', ['task' => $task] + $trailQuery)" />
            @endcan

            @can('delete', $task)
                <x-button-delete :action="route('tasks.destroy', ['task' => $task] + $trailQuery)" message="¿Eliminar tarea?" />
            @endcan

            @include('orders.components.linked-order-action', [
                'action' => $linkedOrderAction,
                'variant' => 'button',
            ])

            <x-button-back :href="$backUrl" :title="$backLabel" :label="$backLabel" />
        </x-page-header>

        <x-show-summary details-id="task-more-detail">
            <x-show-summary-item label="Asignado a">
                {{ $task->assignedUser?->name ?? 'Sin asignar' }}
            </x-show-summary-item>

            <x-show-summary-item label="Proyecto">
                @if ($canViewProject)
                    <a href="{{ route('projects.show', ['project' => $task->project] + $trailQuery) }}">
                        {{ $task->project->name }}
                    </a>
                @else
                    {{ $task->project?->name ?? '—' }}
                @endif
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
                <x-show-summary-item-detail-block label="Vencimiento">
                    {{ $task->due_date?->format('d/m/Y') ?? 'Sin fecha' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Contacto">
                    @include('parties.components.linked-party-action', [
                        'action' => $partyAction,
                        'variant' => 'summary',
                    ])
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Orden asociada">
                    @include('orders.components.linked-order-action', [
                        'action' => $linkedOrderAction,
                        'variant' => 'summary',
                    ])
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $task->description ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones de la tarea">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones de la tarea">
                        <button type="button" class="tabs-link is-active" data-tab-link="attachments" role="tab"
                            aria-selected="true">
                            Adjuntos
                            @if ($attachments->count())
                                ({{ $attachments->count() }})
                            @endif
                        </button>
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            <section class="tab-panel is-active" data-tab-panel="attachments">
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachable' => $task,
                        'attachableType' => 'task',
                        'attachableId' => $task->id,
                        'trailQuery' => $trailQuery,
                        'tabsId' => 'task-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>

    </x-page>
@endsection
