<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Residence;
use App\Notifications\NewContactReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controller pour les contacts (web)
 */
class ContactController extends Controller
{
    /**
     * Envoyer une demande de contact
     */
    public function store(Request $request, Residence $residence): RedirectResponse
    {
        // Vérifier que la résidence est disponible
        if ($residence->status !== 'active' || !$residence->is_available) {
            return back()->with('error', 'Cette résidence n\'est pas disponible.');
        }

        // Vérifier que l'utilisateur ne contacte pas sa propre résidence
        if ($residence->owner_id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas contacter votre propre résidence.');
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $contact = Contact::create([
            'user_id' => $request->user()->id,
            'residence_id' => $residence->id,
            'owner_id' => $residence->owner_id,
            'phone' => $validated['phone'] ?? $request->user()->phone,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        $residence->incrementContacts();

        // Enregistrer le contact sponsorisé si applicable
        if ($residence->isSponsored()) {
            $activeSponsoredListing = $residence->activeSponsoredListing();
            if ($activeSponsoredListing) {
                $activeSponsoredListing->recordContact();
            }
        }

        // Notifier le propriétaire
        $residence->owner->notify(new NewContactReceived($contact, $residence));

        // Notification in-app
        \App\Models\Notification::send(
            $residence->owner,
            'contact',
            'Nouvelle demande de contact',
            ($request->user()->name ?? 'Un visiteur') . ' vous a contacté pour ' . $residence->name,
            route('owner.contacts.show', $contact),
            ['contact_id' => $contact->id, 'residence_id' => $residence->id],
        );

        return back()->with('success', 'Votre demande de contact a été envoyée au propriétaire.');
    }

    /**
     * Mes contacts envoyés
     */
    public function myContacts(Request $request): View
    {
        $contacts = Contact::where('user_id', $request->user()->id)
            ->with(['residence:id,name,commune,quartier,price_per_month', 'owner:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(config('rezi.pagination.contacts'));

        return view('contacts.mine', compact('contacts'));
    }

    /**
     * Voir un contact (propriétaire)
     */
    public function show(Contact $contact): View
    {
        Gate::authorize('view', $contact);

        // Marquer comme vu si c'est le propriétaire
        if (auth()->id() === $contact->owner_id && $contact->status === 'pending') {
            $contact->markAsViewed();
        }

        $contact->load(['user', 'residence']);

        return view('owner.contacts.show', compact('contact'));
    }

    /**
     * Mettre à jour le statut
     */
    public function updateStatus(Request $request, Contact $contact): RedirectResponse
    {
        Gate::authorize('update', $contact);

        $validated = $request->validate([
            'status' => ['required', 'in:viewed,responded,closed'],
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'responded' && !$contact->responded_at) {
            $updateData['responded_at'] = now();
        }

        $contact->update($updateData);

        return back()->with('success', 'Statut mis à jour.');
    }
}
