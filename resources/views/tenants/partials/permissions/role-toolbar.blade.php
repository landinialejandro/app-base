{{-- FILE: resources/views/tenants/partials/permissions/role-toolbar.blade.php | V3 --}}

<x-tab-toolbar>
    <x-slot:tabs>
        <div class="tabs-nav" role="tablist" aria-label="Roles de permisos">
            @foreach ($permissionRoles as $roleSlug => $roleLabel)
                <a href="{{ route('tenant.profile.show', ['tab' => 'permissions', 'role' => $roleSlug]) }}"
                    class="tabs-link {{ $selectedPermissionRole === $roleSlug ? 'is-active' : '' }}"
                    aria-current="{{ $selectedPermissionRole === $roleSlug ? 'page' : 'false' }}">
                    {{ $roleLabel }}
                </a>
            @endforeach
        </div>
    </x-slot:tabs>
</x-tab-toolbar>
