{{-- FILE: resources/views/projects/_form.blade.php --}}

<div class="form-group">
    <label class="form-label" for="name">Nombre</label>
    <input id="name" class="form-control @error('name') is-invalid @enderror" name="name" type="text"
        value="{{ old('name', $project->name ?? '') }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="description">Descripción</label>
    <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description"
        rows="5">{{ old('description', $project->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>