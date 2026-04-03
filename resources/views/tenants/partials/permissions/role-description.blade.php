{{-- FILE: role-description.blade.php | V1 --}}

@switch($role)
    @case('owner')
        Acceso total a la empresa. Puede ver y administrar toda la información.
    @break

    @case('admin')
        Gestiona la empresa junto al propietario. Tiene acceso amplio a todas las áreas.
    @break

    @case('sales')
        Se enfoca en ventas y clientes. Trabaja con órdenes y documentación comercial.
    @break

    @case('operator')
        Realiza tareas de taller o campo. Trabaja sobre la operación técnica diaria.
    @break

    @case('administrator')
        Gestiona la operación administrativa. Puede organizar órdenes, repuestos y documentación sin acceder a configuraciones
        sensibles.
    @break

    @default
        Tipo de acceso no definido.
@endswitch
