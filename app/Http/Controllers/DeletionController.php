<?php
// app/Http/Controllers/DeletionController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;

class DeletionController extends Controller {
    // Usuario solicita su propia baja
    public function request() {
        $user = auth()->user();

        // Verificar si ya tiene solicitud pendiente
        if ($user->hasRequestedDeletion()) {
            return back()->with('error', 'Ya tienes una solicitud de baja pendiente.');
        }

        $user->requestDeletion();

        // Notificar al admin de la empresa
        // TODO: Enviar email

        return back()->with('success', 'Solicitud de baja enviada. Espera la aprobación del administrador.');
    }

    // Admin aprueba baja de usuario
    public function approve(User $user) {
        $this->authorize('approveDeletion', $user); // Verificar permisos

        $user->approveDeletion();

        return back()->with('success', 'Usuario dado de baja.');
    }

    // Admin rechaza baja de usuario
    public function reject(User $user) {
        $this->authorize('approveDeletion', $user);

        $user->rejectDeletion();

        return back()->with('success', 'Solicitud de baja rechazada.');
    }

    // Creador cierra empresa
    public function closeOrganization(Organization $organization, Request $request) {
        $request->validate(['reason' => 'nullable|string|max:255']);

        if (!$organization->canBeClosedBy(auth()->user())) {
            abort(403, 'No tienes permiso para cerrar esta empresa.');
        }

        $organization->close($request->reason);

        // Cerrar sesión del usuario actual si pertenece a esta empresa
        if (auth()->user()->organization_id === $organization->id) {
            auth()->logout();
            return redirect()->route('login')
                ->with('success', 'La empresa ha sido cerrada. Contacta al creador para más información.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Empresa cerrada exitosamente.');
    }
}
