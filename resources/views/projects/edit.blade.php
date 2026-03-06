<h1>Editar proyecto</h1>

<p><strong>Tenant:</strong> {{ $tenant->name }}</p>

<p><a href="{{ route('projects.show',$project) }}">Volver al proyecto</a></p>

<hr>

@if ($errors->any())
<div style="border:1px solid red;padding:10px;margin-bottom:15px;">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('projects.update',$project) }}">
@csrf
@method('PUT')

<div style="margin-bottom:10px">
<label>Nombre</label><br>
<input type="text"
       name="name"
       value="{{ old('name',$project->name) }}"
       style="width:400px">
</div>

<div style="margin-bottom:10px">
<label>Descripción</label><br>
<textarea name="description"
          rows="5"
          style="width:400px">{{ old('description',$project->description) }}</textarea>
</div>

<button type="submit">Actualizar proyecto</button>

</form>