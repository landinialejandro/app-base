<?php

// FILE: app/Http/Controllers/TenantInvitationController.php | V3

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantInvitationController extends Controller
{
    public function store(Request $request)
    {
        $tenant = app('tenant');

        $this->authorize('createTenantInvite', Invitation::class);

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ], [
            'email.required' => 'Ingresa un correo electrónico.',
            'email.email' => 'Ingresa un correo válido.',
        ]);

        $email = strtolower(trim($data['email']));

        $alreadyMember = $tenant->memberships()
            ->whereHas('user', function ($query) use ($email) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->exists();

        if ($alreadyMember) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'users'])
                ->with('error', 'Ese correo ya pertenece a un usuario asociado a esta empresa.');
        }

        $existingPendingInvitation = Invitation::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', 'member_invite')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if ($existingPendingInvitation) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'users'])
                ->with('error', 'Ya existe una invitación pendiente y vigente para ese correo.');
        }

        $invitation = Invitation::create([
            'tenant_id' => $tenant->id,
            'type' => 'member_invite',
            'status' => 'pending',
            'email' => $email,
            'token' => Str::random(64),
            'signup_request_id' => null,
            'invited_by_user_id' => auth()->id(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'accepted_ip' => null,
            'user_agent' => 'Tenant owner',
            'meta' => [
                'source' => 'tenant_profile',
            ],
        ]);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'users'])
            ->with('success', 'Link de invitación generado correctamente.')
            ->with('generated_invitation_id', $invitation->id);
    }

    public function destroy(Invitation $invitation)
    {
        $this->authorize('delete', $invitation);

        $invitation->delete();

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'users'])
            ->with('success', 'Invitación eliminada correctamente.');
    }
}
