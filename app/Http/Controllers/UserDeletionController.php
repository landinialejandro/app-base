<?php
// app/Http/Controllers/UserDeletionController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDeletionController extends Controller
{
    public function showForm()
    {
        return view('deletion.request');
    }
    
    public function submitRequest(Request $request)
    {
        $user = Auth::user();
        
        if ($user->hasRequestedDeletion()) {
            return back()->with('error', 'Ya tienes una solicitud de baja pendiente.');
        }
        
        $user->requestDeletion();
        
        return redirect()->route('dashboard')
            ->with('success', 'Solicitud de baja enviada. El administrador la revisará.');
    }
    
    public function cancelRequest()
    {
        $user = Auth::user();
        $user->rejectDeletion(); // Reutilizamos el método
        
        return redirect()->route('dashboard')
            ->with('success', 'Solicitud de baja cancelada.');
    }
}