{{-- FILE: resources/views/tasks/_form.blade.php | V3 --}}

@php
    use App\Support\Catalogs\TaskCatalog;

    $canChangeProject = $canChangeProject ?? false;
    $defaultAssignedUserId = $defaultAssignedUserId ?? old('assigned_user_id', auth()->id());
    $isForeignTaskForAdmin = $isForeignTaskForAdmin ?? false;
@endphp

<div class="form-group">
    <label for="name" class="form-label">Nombre</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $task->name ?? '') }}"
        required>
    @error('name')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="description" class="form-label">Descripción</label>
    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $task->description ?? '') }}</textarea>
    @error('description')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="status" class="form-label">Estado</label>
    <select name="status" id="status" class="form-control">
        @php
            $currentStatus = old('status', $task->status ?? TaskCatalog::STATUS_PENDING);
        @endphp

        @foreach (TaskCatalog::statusLabels() as $value => $label)
            <option value="{{ $value }}" @selected($currentStatus === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('status')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="priority" class="form-label">Prioridad</label>
    <select name="priority" id="priority" class="form-control">
        @php
            $currentPriority = old('priority', $task->priority ?? TaskCatalog::PRIORITY_MEDIUM);
        @endphp

        @foreach (TaskCatalog::priorityLabels() as $value => $label)
            <option value="{{ $value }}" @selected($currentPriority === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('priority')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

@php
    $lockedProject = !empty($forcedProject) || (!empty($task?->project_id) && !$canChangeProject);
    $lockedProjectId = old('project_id', $forcedProject->id ?? ($task->project_id ?? ''));
    $lockedProjectName = $forcedProject->name ?? ($task->project?->name ?? '');
@endphp

@if ($lockedProject)
    <div class="form-group">
        <label class="form-label">Proyecto</label>
        <input type="text" class="form-control" value="{{ $lockedProjectName }}" disabled>
        <input type="hidden" name="project_id" value="{{ $lockedProjectId }}">
        @if (!empty($task?->project_id) && !$canChangeProject)
            <div class="form-help">Solo owner o admin pueden cambiar el proyecto de una tarea ya vinculada.</div>
        @endif
        @error('project_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
@else
    <div class="form-group">
        <label for="project_id" class="form-label">Proyecto</label>
        <select name="project_id" id="project_id" class="form-control">
            <option value="">Sin proyecto</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((string) old('project_id', $task->project_id ?? '') === (string) $project->id)>
                    {{ $project->name }}
                </option>
            @endforeach
        </select>
        @error('project_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
@endif

<div class="form-group">
    <label for="party_id" class="form-label">Contacto</label>
    <select name="party_id" id="party_id" class="form-control">
        <option value="">Sin contacto</option>
        @foreach ($parties as $party)
            <option value="{{ $party->id }}" @selected((string) old('party_id', $task->party_id ?? '') === (string) $party->id)>
                {{ $party->name }}
            </option>
        @endforeach
    </select>
    @error('party_id')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="assigned_user_id" class="form-label">Asignado a</label>
    <select name="assigned_user_id" id="assigned_user_id" class="form-control">
        <option value="">Asignarme a mí</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $task->assigned_user_id ?? $defaultAssignedUserId) === (string) $user->id)>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    <div class="form-help">La tarea debe quedar asignada a un colaborador. Si no elegís uno, se asigna automáticamente a
        quien la crea o edita.</div>
    @error('assigned_user_id')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="due_date" class="form-label">Vencimiento</label>
    <input type="date" name="due_date" id="due_date" class="form-control"
        value="{{ old('due_date', isset($task->due_date) ? $task->due_date->format('Y-m-d') : '') }}">
    @error('due_date')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

@if ($isForeignTaskForAdmin)
    <div class="form-group">
        <label class="form-label" for="confirm_foreign_task_edit">
            <input class="form-checkbox" type="checkbox" id="confirm_foreign_task_edit" name="confirm_foreign_task_edit"
                value="1" @checked(old('confirm_foreign_task_edit') === '1')>
            Confirmo que estoy modificando una tarea asignada a otro colaborador.
        </label>
        @error('confirm_foreign_task_edit')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
@endif
