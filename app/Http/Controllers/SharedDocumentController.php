<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Residence;
use App\Models\SharedDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedDocumentController extends Controller
{
    /**
     * Liste des documents de l'utilisateur
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $documents = SharedDocument::where('user_id', $user->id)
            ->with('residence')
            ->orderByDesc('created_at')
            ->paginate(20);

        $types = SharedDocument::getTypes();

        return view('documents.index', compact('documents', 'types'));
    }

    /**
     * Formulaire de création
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        // Résidences de l'utilisateur (s'il est propriétaire)
        $residences = Residence::where('owner_id', $user->id)->get();
        $types = SharedDocument::getTypes();

        return view('documents.create', compact('residences', 'types'));
    }

    /**
     * Enregistrer un nouveau document
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:'.implode(',', array_keys(SharedDocument::getTypes())),
            'residence_id' => 'nullable|exists:residences,id',
            'access_type' => 'required|in:public,conversation,private',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        // Vérifier que la résidence appartient à l'utilisateur
        if (isset($validated['residence_id'])) {
            $residence = Residence::find($validated['residence_id']);
            if ($residence->owner_id !== $user->id) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
        }

        // Stocker le fichier
        $path = $file->store("documents/{$user->id}", 'private');

        $document = SharedDocument::create([
            'user_id' => $user->id,
            'residence_id' => $validated['residence_id'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'access_type' => $validated['access_type'],
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'document' => $document,
        ]);
    }

    /**
     * Afficher un document
     */
    public function show(Request $request, SharedDocument $document): View
    {
        $user = $request->user();

        if (!$document->canAccess($user)) {
            abort(403);
        }

        return view('documents.show', compact('document'));
    }

    /**
     * Télécharger un document
     */
    public function download(Request $request, SharedDocument $document): StreamedResponse
    {
        $user = $request->user();

        if (!$document->canAccess($user)) {
            abort(403);
        }

        // Incrémenter le compteur de téléchargements
        $document->incrementDownloads();

        return Storage::disk('private')->download(
            $document->file_path,
            $document->name,
        );
    }

    /**
     * Mettre à jour un document
     */
    public function update(Request $request, SharedDocument $document): JsonResponse
    {
        $user = $request->user();

        if ($document->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:'.implode(',', array_keys(SharedDocument::getTypes())),
            'access_type' => 'sometimes|in:public,conversation,private',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $document->update($validated);

        return response()->json([
            'success' => true,
            'document' => $document->fresh(),
        ]);
    }

    /**
     * Supprimer un document
     */
    public function destroy(Request $request, SharedDocument $document): JsonResponse
    {
        $user = $request->user();

        if ($document->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        // Supprimer le fichier physique
        $document->deleteFile();

        // Supprimer l'enregistrement
        $document->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Obtenir les documents d'une résidence
     */
    public function forResidence(Request $request, Residence $residence): JsonResponse
    {
        $user = $request->user();

        $documents = SharedDocument::forResidence($residence->id)
            ->notExpired()
            ->where(function ($query) use ($user) {
                $query->where('access_type', SharedDocument::ACCESS_PUBLIC)
                      ->orWhere('user_id', $user->id);
            })
            ->get();

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    /**
     * Obtenir les documents partageables dans une conversation
     */
    public function forConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if ($conversation->user_id !== $user->id && $conversation->owner_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        // Documents de l'utilisateur pour cette résidence
        $documents = SharedDocument::where('user_id', $user->id)
            ->where(function ($query) use ($conversation) {
                $query->where('residence_id', $conversation->residence_id)
                      ->orWhereNull('residence_id');
            })
            ->notExpired()
            ->get();

        // Documents déjà partagés dans la conversation
        $sharedDocuments = SharedDocument::forConversation($conversation->id)
            ->notExpired()
            ->get();

        return response()->json([
            'success' => true,
            'available' => $documents,
            'shared' => $sharedDocuments,
        ]);
    }
}
