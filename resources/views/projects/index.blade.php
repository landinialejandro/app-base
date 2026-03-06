<h1>Projects</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><a href="/projects/create">Nuevo proyecto</a></p>
<p><a href="/dashboard">Volver al dashboard</a></p>

<hr>
@if (session('success'))
    <div style="border:1px solid green; padding:10px; margin-bottom:15px;">
        {{ session('success') }}
    </div>
@endif
@if ($projects->isEmpty())
    <p>No hay proyectos para este tenant.</p>
@else
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $project)
                <tr>
                    <td>{{ $project->id }}</td>
                    <td>
                        <a href="{{ route('projects.show', $project) }}">
                            {{ $project->name }}
                        </a>
                    </td>
                    <td>{{ $project->description }}</td>
                    <td>{{ $project->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif