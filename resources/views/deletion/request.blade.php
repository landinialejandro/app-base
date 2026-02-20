{{-- resources/views/deletion/request.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Solicitar Baja de Cuenta') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                    @endif

                    <div class="mb-6">
                        <p class="text-gray-600">
                            <strong>Importante:</strong> Al solicitar tu baja:
                        </p>
                        <ul class="list-disc list-inside mt-2 text-gray-600">
                            <li>Perderás acceso a la plataforma</li>
                            <li>Tus datos serán conservados por seguridad</li>
                            <li>Un administrador debe aprobar la solicitud</li>
                            <li>Puedes cancelar la solicitud mientras esté pendiente</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('deletion.request') }}">
                        @csrf

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Solicitar Baja de Cuenta
                            </button>

                            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>