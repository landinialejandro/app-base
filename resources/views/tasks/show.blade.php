{{-- FILE: resources/views/tasks/show.blade.php | V12 --}}

@extends('layouts.app')

@section('title', $task->name)

@section('content')
    @php
        use App\Models\Order;
        use App\Support\Auth\Security;
        use App\Support\Auth\TenantModuleAccess;
        use App\Support\Catalogs\ModuleCatalog;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Catalogs\TaskCatalog;
        use App\Support\Navigation\NavigationTrail;
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

        $supportsOrdersModule = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);
        $supportsProjectsModule = TenantModuleAccess::isEnabled(ModuleCatalog::PROJECTS, $tenant);
        $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);

        $canViewProject = $supportsProjectsModule && $task->project && $user && $user->can('view', $task->project);

        $canViewParty = $supportsPartiesModule && $task->party && $user && $user->can('view', $task->party);

        $canViewOrder = $supportsOrdersModule && $task->order && $user && $user->can('view', $task->order);

        $canCreateOrderFromTask =
            $supportsOrdersModule &&
            !$task->order &&
            $task->party_id &&
            $user &&
            $user->can('update', $task) &&
            collect(OrderCatalog::kinds())->contains(
                fn(string $kind) => app(Security::class)->allows($user, 'orders.create', Order::class, [
                    'kind' => $kind,
                ]),
            );

        $linkedOrderAction = [
            'supported' => $supportsOrdersModule,
            'linked' => (bool) $task->order,
            'can_view' => $canViewOrder,
            'can_create' => $canCreateOrderFromTask,
            'show_url' => $task->order ? route('orders.show', ['order' => $task->order] + $trailQuery) : null,
            'create_url' => route('orders.create', ['task_id' => $task->id] + $trailQuery),
            'label' => 'Orden',
            'contact_label' => 'Contacto',
            'has_required_party' => (bool) $task->party_id,
            'linked_text' => $task->order?->number ?: 'Ver orden',
        ];
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$task->name">
            @can('update', $task)
                <a href="{{ route('tasks.edit', ['task' => $task] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $task)
                <form method="POST" action="{{ route('tasks.destroy', ['task' => $task] + $trailQuery) }}" class="inline-form"
                    data-action="app-confirm-submit" data-confirm-message="¿Eliminar tarea?">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endcan

            <x-linked-order-action :action="$linkedOrderAction" variant="button" />

            <a href="{{ $backUrl }}" class="btn btn-secondary btn-icon" title="{{ $backLabel }}"
                aria-label="{{ $backLabel }}">
                <x-icons.chevron-left />
            </a>
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
                    @if ($canViewParty)
                        <a href="{{ route('parties.show', ['party' => $task->party] + $trailQuery) }}">
                            {{ $task->party->name }}
                        </a>
                    @else
                        {{ $task->party?->name ?? '—' }}
                    @endif
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Orden asociada">
                    <x-linked-order-action :action="$linkedOrderAction" variant="summary" />
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
