<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\Invitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller {
    /**
     * Display the registration view.
     */
    public function create(Request $request): View {
        return view('auth.register', [
            'email' => $request->email,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        // Si NO hay invitación, requiere organization_name
        if (!session('invitation_token')) {
            $rules['organization_name'] = ['required', 'string', 'max:255', 'unique:organizations,name'];
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class];
        } else {
            // Si hay invitación, el email ya está validado por la invitación
            $invitation = Invitation::where('token', session('invitation_token'))
                ->where('accepted', false)
                ->where('expires_at', '>', now())
                ->firstOrFail();

            $rules['email'] = ['required', 'string', 'email', 'in:' . $invitation->email];
        }

        $request->validate($rules);

        // CASO 1: Registro con invitación
        if (session('invitation_token')) {
            $invitation = Invitation::where('token', session('invitation_token'))->first();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'organization_id' => $invitation->organization_id,
                'role' => $invitation->role,
                'approved_at' => null, // Pendiente de aprobación
            ]);

            // Marcar invitación como aceptada
            $invitation->update(['accepted' => true]);

            // Limpiar sesión
            session()->forget('invitation_token');
        } else {
            // CASO 2: Registro normal (crea organización)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'approved_at' => now(), // Auto-aprobado por ser fundador
            ]);

            $organization = Organization::create([
                'name' => $request->organization_name,
                'slug' => Str::slug($request->organization_name),
                'created_by' => $user->id, // ¡AGREGAR ESTA LÍNEA!
            ]);
            $user->organization_id = $organization->id;
            $user->save();
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
