<div class="form-group">
    <label for="name">Nombre</label>
    <input
        type="text"
        name="name"
        id="name"
        class="form-control"
        value="{{ old('name', $task->name ?? '') }}"
        required
    >
    @error('name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="description">Descripción</label>
    <textarea
        name="description"
        id="description"
        class="form-control"
        rows="4"
    >{{ old('description', $task->description ?? '') }}</textarea>
    @error('description')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="status">Estado</label>
    <select name="status" id="status" class="form-control">
        @php
            $currentStatus = old('status', $task->status ?? 'pending');
        @endphp
        <option value="pending" @selected($currentStatus === 'pending')>Pendiente</option>
        <option value="in_progress" @selected($currentStatus === 'in_progress')>En progreso</option>
        <option value="done" @selected($currentStatus === 'done')>Hecha</option>
        <option value="cancelled" @selected($currentStatus === 'cancelled')>Cancelada</option>
    </select>
    @error('status')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="project_id">Proyecto</label>
    <select name="project_id" id="project_id" class="form-control">
        <option value="">Sin proyecto</option>
        @foreach ($projects as $project)
            <option value="{{ $project->id }}"
                @selected((string) old('project_id', $task->project_id ?? '') === (string) $project->id)>
                {{ $project->name }}
            </option>
        @endforeach
    </select>
    @error('project_id')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="party_id">Contacto</label>
    <select name="party_id" id="party_id" class="form-control">
        <option value="">Sin contacto</option>
        @foreach ($parties as $party)
            <option value="{{ $party->id }}"
                @selected((string) old('party_id', $task->party_id ?? '') === (string) $party->id)>
                {{ $party->name }}
            </option>
        @endforeach
    </select>
    @error('party_id')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="assigned_user_id">Asignado a</label>
    <select name="assigned_user_id" id="assigned_user_id" class="form-control">
        <option value="">Sin asignar</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}"
                @selected((string) old('assigned_user_id', $task->assigned_user_id ?? '') === (string) $user->id)>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    @error('assigned_user_id')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="due_date">Vencimiento</label>
    <input
        type="date"
        name="due_date"
        id="due_date"
        class="form-control"
        value="{{ old('due_date', isset($task->due_date) ? $task->due_date->format('Y-m-d') : '') }}"
    >
    @error('due_date')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>