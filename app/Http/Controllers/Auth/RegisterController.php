<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
        ]);

        // Crear organización
        $organization = Organization::create([
            'name' => $request->organization_name,
        ]);

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'organization_id' => $organization->id,
            'status' => 'active', // o 'pending' si requiere aprobación
        ]);

        // Asignar rol (por defecto 'user' o 'admin' si es el primero)
        if ($organization->users()->count() === 1) {
            $user->assignRole('admin');
        } else {
            $user->assignRole('user');
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect('/app');
    }
}