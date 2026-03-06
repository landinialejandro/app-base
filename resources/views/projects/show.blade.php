<h1>Detalle del proyecto</h1>

<p>
    <a href="{{ route('projects.edit', $project) }}">
        Editar proyecto
    </a>
</p>

<form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('¿Eliminar proyecto?');">
    @csrf
    @method('DELETE')
    <button type="submit">Eliminar proyecto</button>
</form>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>

<p><a href="/projects">Volver a proyectos</a></p>

<hr>

<p><strong>ID:</strong> {{ $project->id }}</p>
<p><strong>Nombre:</strong> {{ $project->name }}</p>
<p><strong>Descripción:</strong> {{ $project->description }}</p>
<p><strong>Creado:</strong> {{ $project->created_at }}</p>
<p><strong>Actualizado:</strong> {{ $project->updated_at }}</p>