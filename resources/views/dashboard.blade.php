
<h1>Tenant Dashboard</h1>

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Cerrar sesión</button>
</form>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>
<p><strong>Slug:</strong> {{ $tenant->slug }}</p>
<p><strong>Projects:</strong> {{ $projectsCount }}</p>

<hr>

<p><a href="/test-project">Crear proyecto de prueba</a></p>
<p><a href="/projects">Ver proyectos</a></p>