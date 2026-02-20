<?php
// app/Http/Controllers/InvitationController.php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function create()
    {
        return view('invitations.create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:user,supervisor,admin'],
        ]);
        
        $organization = auth()->user()->organization;
        
        // Verificar que no exista invitación pendiente
        $existing = Invitation::where('email', $request->email)
            ->where('organization_id', $organization->id)
            ->where('accepted', false)
            ->where('expires_at', '>', now())
            ->first();
            
        if ($existing) {
            return back()->withErrors(['email' => 'Ya existe una invitación pendiente para este email.']);
        }
        
        $invitation = Invitation::create([
            'email' => $request->email,
            'organization_id' => $organization->id,
            'token' => Invitation::generateToken(),
            'role' => $request->role,
            'expires_at' => now()->addDays(7),
        ]);
        
        // TODO: Enviar email
        // Por ahora solo mostramos el link en pantalla
        
        return back()->with('success', 'Invitación creada. Link: ' . route('invitations.accept', $invitation->token));
    }

     public function accept($token)
    {
        $invitation = Invitation::where('token', $token)
            ->where('accepted', false)
            ->where('expires_at', '>', now())
            ->firstOrFail();
        
        // Guardar token en sesión para el registro
        session(['invitation_token' => $invitation->token]);
        
        // Redirigir al registro con email pre-llenado
        return redirect()->route('register', ['email' => $invitation->email]);
    }
}