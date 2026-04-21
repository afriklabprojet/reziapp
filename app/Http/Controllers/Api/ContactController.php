<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Residence;
use App\Notifications\NewContactReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller API pour les contacts
 */
class ContactController extends Controller
{
    /**
     * Envoyer une demande de contact
     */
    public function store(Request $request, Residence $residence): JsonResponse
    {
        // Vérifier que la résidence est disponible
        if ($residence->status !== 'active' || !$residence->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Cette résidence n\'est pas disponible.',
            ], 400);
        }

        // Vérifier que l'utilisateur ne contacte pas sa propre résidence
        if ($residence->owner_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas contacter votre propre résidence.',
            ], 400);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $contact = Contact::create([
            'user_id' => $request->user()->id,
            'residence_id' => $residence->id,
            'owner_id' => $residence->owner_id,
            'phone' => $validated['phone'] ?? $request->user()->phone,
            'message' => $validated['message'] ?? null,
            'user_latitude' => $validated['latitude'] ?? null,
            'user_longitude' => $validated['longitude'] ?? null,
            'status' => 'pending',
        ]);

        // Incrémenter le compteur de contacts
        $residence->incrementContacts();

        // Notifier le propriétaire
        $residence->owner->notify(new NewContactReceived($contact, $residence));

        // Notification in-app
        \App\Models\Notification::send(
            $residence->owner,
            'contact',
            'Nouvelle demande de contact',
            ($request->user()->name ?? 'Un visiteur').' vous a contacté pour '.$residence->name,
            route('owner.contacts.show', $contact),
            ['contact_id' => $contact->id, 'residence_id' => $residence->id],
        );

        Log::info('Nouvelle demande de contact', [
            'contact_id' => $contact->id,
            'user_id' => $request->user()->id,
            'residence_id' => $residence->id,
            'owner_id' => $residence->owner_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de contact envoyée.',
            'data' => [
                'id' => $contact->id,
                'residence' => $residence->name,
                'status' => $contact->status,
                'created_at' => $contact->created_at,
            ],
        ], 201);
    }

    /**
     * Mes contacts envoyés (utilisateur)
     */
    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::where('user_id', $request->user()->id)
            ->with(['residence:id,name,commune,quartier', 'owner:id,name,phone'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    /**
     * Contacts reçus (propriétaire)
     */
    public function ownerContacts(Request $request): JsonResponse
    {
        $contacts = Contact::where('owner_id', $request->user()->id)
            ->with([
                'user:id,name,email,phone',
                'residence:id,name,commune,quartier',
            ])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Statistiques rapides
        $stats = [
            'pending' => Contact::where('owner_id', $request->user()->id)->where('status', 'pending')->count(),
            'total' => Contact::where('owner_id', $request->user()->id)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $contacts,
            'stats' => $stats,
        ]);
    }

    /**
     * Mettre à jour le statut d'un contact (propriétaire)
     */
    public function updateStatus(Request $request, Contact $contact): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:viewed,responded,closed'],
        ]);

        $oldStatus = $contact->status;

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'viewed' && !$contact->viewed_at) {
            $updateData['viewed_at'] = now();
        }

        if ($validated['status'] === 'responded' && !$contact->responded_at) {
            $updateData['responded_at'] = now();
        }

        $contact->update($updateData);

        Log::info('Statut contact mis à jour', [
            'contact_id' => $contact->id,
            'owner_id' => $request->user()->id,
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour.',
            'data' => [
                'id' => $contact->id,
                'status' => $contact->status,
                'viewed_at' => $contact->viewed_at,
                'responded_at' => $contact->responded_at,
            ],
        ]);
    }
}
