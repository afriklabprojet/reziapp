<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\CoHost;
use App\Models\Residence;
use App\Models\User;
use App\Notifications\CoHostInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CoHostController extends Controller
{
    /**
     * Liste les co-hôtes d'une résidence
     */
    public function index(Residence $residence)
    {
        $this->authorize('update', $residence);

        $coHosts = $residence->coHosts()
            ->with(['user', 'activities' => fn ($q) => $q->latest()->limit(5)])
            ->orderByRaw("CASE status WHEN 'accepted' THEN 1 WHEN 'pending' THEN 2 WHEN 'declined' THEN 3 WHEN 'revoked' THEN 4 ELSE 5 END")
            ->get();

        return view('owner.cohosts.index', compact('residence', 'coHosts'));
    }

    /**
     * Formulaire d'invitation d'un co-hôte
     */
    public function create(Residence $residence)
    {
        $this->authorize('update', $residence);

        return view('owner.cohosts.create', compact('residence'));
    }

    /**
     * Envoie une invitation à un co-hôte
     */
    public function store(Request $request, Residence $residence)
    {
        $this->authorize('update', $residence);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'can_edit_listing' => 'boolean',
            'can_manage_calendar' => 'boolean',
            'can_manage_pricing' => 'boolean',
            'can_respond_messages' => 'boolean',
            'can_accept_bookings' => 'boolean',
            'can_view_earnings' => 'boolean',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Vérifier si déjà invité
        $existingInvite = $residence->coHosts()
            ->where('email', $validated['email'])
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingInvite) {
            return back()
                ->withInput()
                ->with('error', 'Cette personne a déjà été invitée ou est déjà co-hôte.');
        }

        // Vérifier si l'email correspond à un utilisateur existant
        $existingUser = User::where('email', $validated['email'])->first();

        $coHost = $residence->coHosts()->create([
            'owner_id' => Auth::id(),
            'user_id' => $existingUser?->id,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'can_edit_listing' => $validated['can_edit_listing'] ?? false,
            'can_manage_calendar' => $validated['can_manage_calendar'] ?? true,
            'can_manage_pricing' => $validated['can_manage_pricing'] ?? false,
            'can_respond_messages' => $validated['can_respond_messages'] ?? true,
            'can_accept_bookings' => $validated['can_accept_bookings'] ?? false,
            'can_view_earnings' => $validated['can_view_earnings'] ?? false,
            'commission_percent' => $validated['commission_percent'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'invitation_token' => Str::random(64),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        // Envoyer l'email d'invitation
        Notification::route('mail', $validated['email'])
            ->notify(new CoHostInvitation($coHost, $residence));

        return redirect()
            ->route('owner.cohosts.index', $residence)
            ->with('success', "Invitation envoyée à {$validated['name']}.");
    }

    /**
     * Affiche les détails d'un co-hôte
     */
    public function show(Residence $residence, CoHost $cohost)
    {
        $this->authorize('update', $residence);

        $cohost->load(['user', 'activities' => fn ($q) => $q->latest()->limit(20)]);

        return view('owner.cohosts.show', compact('residence', 'cohost'));
    }

    /**
     * Formulaire d'édition des permissions
     */
    public function edit(Residence $residence, CoHost $cohost)
    {
        $this->authorize('update', $residence);

        return view('owner.cohosts.edit', compact('residence', 'cohost'));
    }

    /**
     * Met à jour les permissions d'un co-hôte
     */
    public function update(Request $request, Residence $residence, CoHost $cohost)
    {
        $this->authorize('update', $residence);

        $validated = $request->validate([
            'can_edit_listing' => 'boolean',
            'can_manage_calendar' => 'boolean',
            'can_manage_pricing' => 'boolean',
            'can_respond_messages' => 'boolean',
            'can_accept_bookings' => 'boolean',
            'can_view_earnings' => 'boolean',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $cohost->update([
            'can_edit_listing' => $validated['can_edit_listing'] ?? false,
            'can_manage_calendar' => $validated['can_manage_calendar'] ?? false,
            'can_manage_pricing' => $validated['can_manage_pricing'] ?? false,
            'can_respond_messages' => $validated['can_respond_messages'] ?? false,
            'can_accept_bookings' => $validated['can_accept_bookings'] ?? false,
            'can_view_earnings' => $validated['can_view_earnings'] ?? false,
            'commission_percent' => $validated['commission_percent'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('owner.cohosts.index', $residence)
            ->with('success', 'Permissions mises à jour.');
    }

    /**
     * Révoque l'accès d'un co-hôte
     */
    public function revoke(Residence $residence, CoHost $cohost)
    {
        $this->authorize('update', $residence);

        $cohost->revoke();

        return redirect()
            ->route('owner.cohosts.index', $residence)
            ->with('success', "L'accès de {$cohost->name} a été révoqué.");
    }

    /**
     * Renvoie l'invitation
     */
    public function resend(Residence $residence, CoHost $cohost)
    {
        $this->authorize('update', $residence);

        if ($cohost->status !== 'pending') {
            return back()->with('error', 'Cette invitation ne peut pas être renvoyée.');
        }

        $cohost->update([
            'invitation_token' => Str::random(64),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        // Renvoyer l'email d'invitation
        Notification::route('mail', $cohost->email)
            ->notify(new CoHostInvitation($cohost, $residence));

        return back()->with('success', 'Invitation renvoyée.');
    }

    /**
     * Supprime un co-hôte
     */
    public function destroy(Residence $residence, CoHost $cohost)
    {
        $this->authorize('update', $residence);

        $name = $cohost->name;
        $cohost->delete();

        return redirect()
            ->route('owner.cohosts.index', $residence)
            ->with('success', "Co-hôte {$name} supprimé.");
    }

    /**
     * Page publique pour accepter l'invitation
     */
    public function acceptInvitation(string $token)
    {
        $cohost = CoHost::where('invitation_token', $token)
            ->where('status', 'pending')
            ->notExpired()
            ->firstOrFail();

        return view('cohosts.accept', compact('cohost'));
    }

    /**
     * Traite l'acceptation de l'invitation
     */
    public function processAcceptInvitation(Request $request, string $token)
    {
        $cohost = CoHost::where('invitation_token', $token)
            ->where('status', 'pending')
            ->notExpired()
            ->firstOrFail();

        // Si l'utilisateur est connecté
        if (Auth::check()) {
            if (Auth::user()->email !== $cohost->email) {
                return back()->with('error', 'Cette invitation est destinée à une autre adresse email.');
            }

            $cohost->accept(Auth::user());

            return redirect()
                ->route('owner.dashboard')
                ->with('success', "Vous êtes maintenant co-hôte de {$cohost->residence->name}.");
        }

        // Si l'utilisateur n'est pas connecté, le rediriger vers l'inscription
        return redirect()
            ->route('register', ['cohost_token' => $token])
            ->with('info', 'Créez un compte pour accepter l\'invitation.');
    }

    /**
     * Refuse l'invitation
     */
    public function declineInvitation(string $token)
    {
        $cohost = CoHost::where('invitation_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $cohost->decline();

        return redirect()
            ->route('home')
            ->with('info', 'Invitation refusée.');
    }
}
