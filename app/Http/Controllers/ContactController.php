<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Residence;
use App\Models\User;
use App\Notifications\NewContactReceived;
use App\Services\ResidenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Controller pour les contacts (web)
 */
class ContactController extends Controller
{
    public function __construct(private readonly ResidenceService $residenceService)
    {
    }

    /**
     * Envoyer une demande de contact
     */
    public function store(Request $request, Residence $residence): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $this->ensureCanContactResidence($user, $residence);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $contact = Contact::create([
            'user_id' => $user->id,
            'residence_id' => $residence->id,
            'owner_id' => $residence->owner_id,
            'phone' => $validated['phone'] ?? $user->phone,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        $this->residenceService->recordContact($residence, $user->id);

        // Notifier le propriétaire
        $residence->owner->notify(new NewContactReceived($contact, $residence));

        // Notification in-app
        \App\Models\Notification::send(
            $residence->owner,
            'contact',
            'Nouvelle demande de contact',
            ($user->name ?? 'Un visiteur').' vous a contacté pour '.$residence->name,
            route('owner.contacts.show', $contact),
            ['contact_id' => $contact->id, 'residence_id' => $residence->id],
        );

        return back()->with('success', 'Votre demande de contact a été envoyée au propriétaire.');
    }

    private function ensureCanContactResidence(User $user, Residence $residence): void
    {
        if ($residence->status !== 'active' || !$residence->is_available) {
            throw ValidationException::withMessages([
                'residence' => 'Cette résidence n\'est pas disponible.',
            ]);
        }

        if ($residence->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'residence' => 'Vous ne pouvez pas contacter votre propre résidence.',
            ]);
        }

        $hasBooking = \App\Models\Booking::where('user_id', $user->id)
            ->where('residence_id', $residence->id)
            ->whereNotIn('status', ['cancelled', 'expired'])
            ->exists();

        if (!$hasBooking) {
            throw ValidationException::withMessages([
                'residence' => 'Vous devez effectuer une réservation avant de contacter le propriétaire.',
            ]);
        }
    }

    /**
     * Mes contacts envoyés
     */
    public function myContacts(Request $request): View
    {
        /** @var \App\Models\User $me */
        $me = $request->user();
        $contacts = Contact::where('user_id', $me->id)
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
        /** @var \App\Models\User $authUser */
        $authUser = \Illuminate\Support\Facades\Auth::user();
        if ($authUser->id === $contact->owner_id && $contact->status === 'pending') {
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
