<?php

// FILE: app/Http/Controllers/AdminInvitationController.php

namespace App\Http\Controllers;

use App\Models\Invitation;

class AdminInvitationController extends Controller
{
    public function ownerSignups()
    {
        $invitations = Invitation::query()
            ->where('type', 'owner_signup')
            ->whereNotNull('sent_at')
            ->whereNull('accepted_at')
            ->latest()
            ->paginate(10);

        return view('admin.invitations.owner-signups', [
            'invitations' => $invitations,
        ]);
    }

    public function markAsSent(Invitation $invitation)
    {
        abort_unless($invitation->type === 'owner_signup', 404);

        if ($invitation->sent_at) {
            return redirect()
                ->back()
                ->with('error', 'La invitación ya fue marcada como enviada.');
        }

        $invitation->update([
            'sent_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Invitación marcada como enviada correctamente.');
    }
}
