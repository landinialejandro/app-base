{{-- resources/views/profile/partials/update-profile-information-form.blade.php --}}
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- SECCIÓN DE ORGANIZACIÓN (solo para no superadmins) --}}
        @if(!$user->is_platform_admin)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Información de Organización</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Nombre de la empresa --}}
                <div>
                    <x-input-label for="organization_name" :value="__('Organización')" />
                    
                    @if($user->role === 'admin')
                        {{-- Admin puede editar --}}
                        <x-text-input id="organization_name" 
                                      name="organization_name" 
                                      type="text" 
                                      class="mt-1 block w-full" 
                                      :value="old('organization_name', $user->organization->name ?? '')" />
                        <p class="text-xs text-gray-500 mt-1">Como admin puedes cambiar el nombre de tu empresa</p>
                    @else
                        {{-- Usuario normal solo ve --}}
                        <p class="mt-1 block w-full py-2 px-3 bg-gray-100 rounded-md border border-gray-300 text-gray-700">
                            {{ $user->organization->name ?? 'No asignada' }}
                        </p>
                    @endif
                </div>
                
                {{-- Rol del usuario --}}
                <div>
                    <x-input-label :value="__('Tu Rol')" />
                    <div class="mt-1 flex items-center">
                        <span class="px-3 py-2 bg-gray-100 rounded-md border border-gray-300 text-gray-700 capitalize">
                            {{ $user->role }}
                        </span>
                        @if($user->role === 'admin')
                            <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Administrador</span>
                        @elseif($user->role === 'supervisor')
                            <span class="ml-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Supervisor</span>
                        @else
                            <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Usuario</span>
                        @endif
                    </div>
                    @if($user->role !== 'admin')
                        <p class="text-xs text-gray-500 mt-1">Tu rol es asignado por el administrador</p>
                    @endif
                </div>
            </div>
            
            {{-- Estado de la cuenta --}}
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label :value="__('Estado')" />
                    <div class="mt-1">
                        @if($user->approved_at)
                            <span class="text-green-600 text-sm">✓ Cuenta aprobada</span>
                        @else
                            <span class="text-yellow-600 text-sm">⏳ Pendiente de aprobación</span>
                        @endif
                    </div>
                </div>
                
                @if($user->deletion_requested_at && !$user->deletion_approved)
                <div>
                    <x-input-label :value="__('Solicitud de baja')" />
                    <div class="mt-1">
                        <span class="text-red-600 text-sm">⚠️ Solicitud pendiente desde {{ $user->deletion_requested_at->format('d/m/Y') }}</span>
                    </div>
                </div>
                @endif
            </div>
            
            {{-- Nota para admin --}}
            @if($user->role === 'admin')
                <div class="mt-3 text-xs text-amber-600 bg-amber-50 p-2 rounded">
                    <strong>Nota:</strong> Como administrador, puedes cambiar el nombre de tu empresa. 
                    Para otros cambios (como tu rol), contacta al superadmin.
                </div>
            @endif
        </div>
        @else
        {{-- Mensaje para superadmin --}}
        <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
            <h3 class="text-lg font-medium text-purple-900 mb-2">Super Administrador de Plataforma</h3>
            <p class="text-sm text-purple-700">Tienes acceso global a todas las organizaciones.</p>
        </div>
        @endif

        {{-- SECCIÓN DE DATOS PERSONALES --}}
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Tus Datos Personales</h3>
            
            {{-- Nombre --}}
            <div class="mb-4">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" 
                              name="name" 
                              type="text" 
                              class="mt-1 block w-full" 
                              :value="old('name', $user->name)" 
                              required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" 
                              name="email" 
                              type="email" 
                              class="mt-1 block w-full" 
                              :value="old('email', $user->email)" 
                              required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-2">
                        <p class="text-sm text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button form="send-verification" 
                                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- BOTONES --}}
        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }"
                   x-show="show"
                   x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600">
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>