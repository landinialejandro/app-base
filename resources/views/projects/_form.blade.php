<div class="form-group">
    <label for="name" class="form-label">Nombre</label>
    <input
        type="text"
        id="name"
        name="name"
        class="form-control"
        value="{{ old('name', $project->name ?? '') }}"
        required
    >
</div>

<div class="form-group">
    <label for="description" class="form-label">Descripción</label>
    <textarea
        id="description"
        name="description"
        class="form-control"
        rows="4"
    >{{ old('description', $project->description ?? '') }}</textarea>
</div>