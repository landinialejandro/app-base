{{-- resources/views/dashboard.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Tarjeta de bienvenida con información de empresa -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">¡Bienvenido, {{ Auth::user()->name }}!</h3>

                    @if(Auth::user()->is_platform_admin)
                    <p class="mt-2 text-sm text-gray-600">Rol: Super Administrador de Plataforma</p>
                    @else
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded">
                            <p class="text-sm text-gray-500">Organización</p>
                            <p class="text-lg font-medium">{{ Auth::user()->organization->name ?? 'Sin organización' }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <p class="text-sm text-gray-500">Tu Rol</p>
                            <p class="text-lg font-medium capitalize">{{ Auth::user()->role }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Acciones rápidas -->
            @if(!Auth::user()->is_platform_admin)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="font-semibold text-gray-700 mb-4">Acciones Rápidas</h4>
                    <div class="flex flex-wrap gap-4">
                        @if(Auth::user()->role === 'admin')
                        <a href="{{ url('/admin/users') }}"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Gestionar Usuarios
                        </a>
                        <a href="{{ route('invitations.create') }}"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Invitar Usuarios
                        </a>
                        @endif
                        <a href="{{ route('profile.edit') }}"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Mi Perfil
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>