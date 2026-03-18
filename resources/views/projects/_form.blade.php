{{-- FILE: resources/views/projects/_form.blade.php --}}

@php
    use App\Support\Catalogs\ProjectCatalog;
@endphp

<div class="form-group">
    <label class="form-label" for="name">Nombre</label>
    <input id="name" class="form-control" name="name" type="text" value="{{ old('name', $project->name ?? '') }}"
        required>
    @error('name')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="status">Estado</label>
    <select id="status" name="status" class="form-control">
        @foreach (ProjectCatalog::statusLabels() as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $project->status ?? ProjectCatalog::STATUS_ACTIVE) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('status')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="description">Descripción</label>
    <textarea id="description" class="form-control" name="description" rows="5">{{ old('description', $project->description ?? '') }}</textarea>
    @error('description')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>
