<?php

// FILE: app/Http/Controllers/InvitationAcceptanceController.php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InvitationAcceptanceController extends Controller
{
    public function show(Request $request, string $token)
    {
        $invitation = Invitation::query()
            ->with('signupRequest')
            ->where('token', $token)
            ->firstOrFail();

        $existingUser = User::query()
            ->where('email', $invitation->email)
            ->first();

        $emailExists = (bool) $existingUser;

        if ($invitation->accepted_at || $invitation->status === 'accepted') {
            return view('invitations.show', [
                'invitation' => $invitation,
                'state' => 'accepted',
                'emailExists' => $emailExists,
                'mustLogin' => false,
                'prefillUser' => null,
            ]);
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return view('invitations.show', [
                'invitation' => $invitation,
                'state' => 'expired',
                'emailExists' => $emailExists,
                'mustLogin' => false,
                'prefillUser' => null,
            ]);
        }

        if ($emailExists && Auth::check() && Auth::user()->email !== $invitation->email) {
            Auth::guard('web')->logout();
            $request->session()->forget('tenant_id');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $request->session()->put('invitation_accept_url', route('invitation.accept.show', $invitation->token));

            return redirect()
                ->route('login')
                ->with('error', 'Se cerró la sesión activa. Inicia sesión con el email invitado para continuar.');
        }

        $mustLogin = $emailExists && !Auth::check();

        if ($mustLogin) {
            $request->session()->put(
                'invitation_accept_url',
                route('invitation.accept.show', $invitation->token)
            );
        }

        return view('invitations.show', [
            'invitation' => $invitation,
            'state' => 'valid',
            'emailExists' => $emailExists,
            'mustLogin' => $mustLogin,
            'prefillUser' => $existingUser,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $invitation = Invitation::query()
            ->with('signupRequest')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->accepted_at || $invitation->status === 'accepted') {
            return redirect()
                ->route('invitation.accept.show', $invitation->token)
                ->with('error', 'Esta invitación ya fue aceptada.');
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return redirect()
                ->route('invitation.accept.show', $invitation->token)
                ->with('error', 'Esta invitación está vencida.');
        }

        $existingUser = User::query()
            ->where('email', $invitation->email)
            ->first();

        if ($invitation->type === 'member_invite') {
            if (!$invitation->tenant_id) {
                return redirect()
                    ->route('invitation.accept.show', $invitation->token)
                    ->with('error', 'La invitación de miembro no tiene tenant asociado.');
            }

            return $this->handleMemberInvite($request, $invitation, $existingUser);
        }

        if ($invitation->type === 'owner_signup') {
            if (!$invitation->signup_request_id) {
                return redirect()
                    ->route('invitation.accept.show', $invitation->token)
                    ->with('error', 'La invitación de owner no tiene solicitud asociada.');
            }

            if (is_null($invitation->accepted_at) && !is_null($invitation->tenant_id)) {
                return redirect()
                    ->route('invitation.accept.show', $invitation->token)
                    ->with('error', 'La invitación owner tiene un tenant asignado antes de ser aceptada.');
            }

            return $this->handleOwnerSignup($request, $invitation, $existingUser);
        }

        return redirect()
            ->route('invitation.accept.show', $invitation->token)
            ->with('error', 'Tipo de invitación no soportado.');
    }

    protected function handleMemberInvite(Request $request, Invitation $invitation, ?User $existingUser)
    {
        if ($existingUser) {
            if (!Auth::check()) {
                $request->session()->put('invitation_accept_url', route('invitation.accept.show', $invitation->token));

                return redirect()
                    ->route('login')
                    ->with('error', 'Inicia sesión con el email invitado para continuar.');
            }

            if (Auth::user()->email !== $invitation->email) {
                Auth::guard('web')->logout();
                $request->session()->forget('tenant_id');
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $request->session()->put('invitation_accept_url', route('invitation.accept.show', $invitation->token));

                return redirect()
                    ->route('login')
                    ->with('error', 'Debes iniciar sesión con el email asociado a la invitación.');
            }

            DB::transaction(function () use ($invitation, $existingUser, $request) {
                $this->attachMembershipToExistingTenant($invitation, $existingUser);
                $this->markInvitationAccepted($invitation, $request);
            });

            return $this->redirectAfterAcceptance($request, $existingUser);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = DB::transaction(function () use ($invitation, $data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => Hash::make($data['password']),
            ]);

            $this->attachMembershipToExistingTenant($invitation, $user);
            $this->markInvitationAccepted($invitation, $request);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectAfterAcceptance($request, $user);
    }

    protected function handleOwnerSignup(Request $request, Invitation $invitation, ?User $existingUser)
    {
        if ($existingUser) {
            if (!Auth::check()) {
                $request->session()->put('invitation_accept_url', route('invitation.accept.show', $invitation->token));

                return redirect()
                    ->route('login')
                    ->with('error', 'Inicia sesión con el email invitado para completar el alta de la nueva empresa.');
            }

            if (Auth::user()->email !== $invitation->email) {
                Auth::guard('web')->logout();
                $request->session()->forget('tenant_id');
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $request->session()->put('invitation_accept_url', route('invitation.accept.show', $invitation->token));

                return redirect()
                    ->route('login')
                    ->with('error', 'Debes iniciar sesión con el email asociado a la invitación.');
            }

            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);
        } else {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);
        }

        $user = DB::transaction(function () use ($invitation, $existingUser, $data, $request) {
            $user = $existingUser;

            if (!$user) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $invitation->email,
                    'password' => Hash::make($data['password']),
                ]);
            } else {
                $user->update([
                    'name' => $data['name'],
                ]);
            }

            $tenant = $this->createTenantForOwnerInvitation($invitation);

            Membership::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                ],
                [
                    'status' => 'active',
                    'is_owner' => true,
                    'joined_at' => now(),
                ]
            );

            $invitation->tenant_id = $tenant->id;
            $this->markInvitationAccepted($invitation, $request);

            return $user;
        });

        if (!Auth::check()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return $this->redirectAfterAcceptance($request, $user);
    }

    protected function attachMembershipToExistingTenant(Invitation $invitation, User $user): void
    {
        if (!$invitation->tenant_id) {
            abort(500, 'La invitación de miembro no tiene tenant asociado.');
        }

        Membership::firstOrCreate(
            [
                'tenant_id' => $invitation->tenant_id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'active',
                'is_owner' => false,
                'joined_at' => now(),
            ]
        );
    }

    protected function createTenantForOwnerInvitation(Invitation $invitation): Tenant
    {
        if ($invitation->tenant_id) {
            $tenant = Tenant::query()->find($invitation->tenant_id);

            if ($tenant) {
                return $tenant;
            }
        }

        $signupRequest = $invitation->signupRequest;

        if (!$signupRequest) {
            abort(500, 'La invitación de owner no tiene solicitud asociada.');
        }

        return Tenant::create([
            'name' => $signupRequest->company_name,
            'slug' => $this->generateUniqueTenantSlug($signupRequest->company_name),
            'settings' => [],
        ]);
    }

    protected function markInvitationAccepted(Invitation $invitation, Request $request): void
    {
        $invitation->update([
            'tenant_id' => $invitation->tenant_id,
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    protected function redirectAfterAcceptance(Request $request, User $user)
    {
        $tenantsCount = $user->tenants()->count();

        if ($tenantsCount > 1) {
            return redirect()
                ->route('tenants.select')
                ->with('success', 'Invitación aceptada correctamente.');
        }

        if ($tenantsCount === 1) {
            $tenantId = $user->tenants()->value('tenants.id');

            $request->session()->put('tenant_id', $tenantId);

            return redirect()
                ->route('dashboard')
                ->with('success', 'Invitación aceptada correctamente.');
        }

        return redirect()
            ->route('tenants.select')
            ->with('success', 'Invitación aceptada correctamente.');
    }

    protected function generateUniqueTenantSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'empresa';
        }

        $slug = $base;
        $counter = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}