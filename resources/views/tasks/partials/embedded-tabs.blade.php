{{-- FILE: resources/views/tasks/partials/embedded-tabs.blade.php | V1 --}}

@php
    $tasks = $tasks ?? collect();
    $openTasks = $openTasks ?? collect();
    $doneTasks = $doneTasks ?? collect();

    $emptyMessageOpen = $emptyMessageOpen ?? 'No hay tareas abiertas para mostrar.';
    $emptyMessageDone = $emptyMessageDone ?? 'No hay tareas finalizadas para mostrar.';
    $emptyMessageAll = $emptyMessageAll ?? 'No hay tareas para mostrar.';

    $tabsId = $tabsId ?? 'tasks-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
    $createBaseQuery = $createBaseQuery ?? [];
@endphp

<div class="tabs" data-tabs>
    @php
        $toolbarAction = route('tasks.create', $createBaseQuery + $trailQuery);
    @endphp

    <x-tab-toolbar label="Secciones de tareas">
        <x-slot:tabs>
            <x-horizontal-scroll label="Secciones de tareas">
                <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-open" role="tab"
                    aria-selected="true">
                    Abiertas
                    @if ($openTasks->count())
                        ({{ $openTasks->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-done" role="tab"
                    aria-selected="false">
                    Finalizadas
                    @if ($doneTasks->count())
                        ({{ $doneTasks->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-all" role="tab"
                    aria-selected="false">
                    Todas
                    @if ($tasks->count())
                        ({{ $tasks->count() }})
                    @endif
                </button>
            </x-horizontal-scroll>
        </x-slot:tabs>

        <x-slot:actions>
            @can('create', App\Models\Task::class)
                <a href="{{ $toolbarAction }}" class="btn btn-success btn-sm">
                    <x-icons.plus />
                    <span>Agregar tarea</span>
                </a>
            @endcan
        </x-slot:actions>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-open">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('tasks.partials.table', [
                    'tasks' => $openTasks,
                    'emptyMessage' => $emptyMessageOpen,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    <section class="tab-panel" data-tab-panel="{{ $tabsId }}-done" hidden>
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('tasks.partials.table', [
                    'tasks' => $doneTasks,
                    'emptyMessage' => $emptyMessageDone,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    <section class="tab-panel" data-tab-panel="{{ $tabsId }}-all" hidden>
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('tasks.partials.table', [
                    'tasks' => $tasks,
                    'emptyMessage' => $emptyMessageAll,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>
</div>
