<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AutoReply;
use App\Models\Residence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutoReplyController extends Controller
{
    /**
     * Liste des réponses automatiques
     */
    public function index()
    {
        $autoReplies = AutoReply::where('user_id', Auth::id())
            ->with('residence')
            ->orderBy('is_active', 'desc')
            ->orderBy('usage_count', 'desc')
            ->get();

        return view('owner.auto-replies.index', compact('autoReplies'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $residences = Residence::where('owner_id', Auth::id())->get();

        return view('owner.auto-replies.create', compact('residences'));
    }

    /**
     * Enregistre une nouvelle réponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'residence_id' => 'nullable|exists:residences,id',
            'trigger_type' => 'required|in:first_contact,keywords,schedule,manual',
            'trigger_conditions' => 'nullable|array',
            'trigger_conditions.keywords' => 'nullable|array',
            'trigger_conditions.keywords.*' => 'string|max:50',
            'trigger_conditions.start_time' => 'nullable|date_format:H:i',
            'trigger_conditions.end_time' => 'nullable|date_format:H:i',
            'trigger_conditions.days' => 'nullable|array',
            'trigger_conditions.days.*' => 'integer|between:0,6',
            'message' => 'required|string|max:2000',
            'delay_minutes' => 'nullable|integer|min:0|max:60',
        ]);

        // Vérifier que la résidence appartient à l'utilisateur
        if (!empty($validated['residence_id'])) {
            $residence = Residence::findOrFail($validated['residence_id']);
            if ($residence->owner_id !== Auth::id()) {
                abort(403);
            }
        }

        AutoReply::create([
            'user_id' => Auth::id(),
            'residence_id' => $validated['residence_id'] ?? null,
            'name' => $validated['name'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_conditions' => $validated['trigger_conditions'] ?? null,
            'message' => $validated['message'],
            'delay_minutes' => $validated['delay_minutes'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()
            ->route('owner.auto-replies.index')
            ->with('success', 'Réponse automatique créée avec succès.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(AutoReply $autoReply)
    {
        $this->authorize('update', $autoReply);

        $residences = Residence::where('owner_id', Auth::id())->get();

        return view('owner.auto-replies.edit', compact('autoReply', 'residences'));
    }

    /**
     * Met à jour une réponse
     */
    public function update(Request $request, AutoReply $autoReply)
    {
        $this->authorize('update', $autoReply);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'residence_id' => 'nullable|exists:residences,id',
            'trigger_type' => 'required|in:first_contact,keywords,schedule,manual',
            'trigger_conditions' => 'nullable|array',
            'trigger_conditions.keywords' => 'nullable|array',
            'trigger_conditions.keywords.*' => 'string|max:50',
            'trigger_conditions.start_time' => 'nullable|date_format:H:i',
            'trigger_conditions.end_time' => 'nullable|date_format:H:i',
            'trigger_conditions.days' => 'nullable|array',
            'trigger_conditions.days.*' => 'integer|between:0,6',
            'message' => 'required|string|max:2000',
            'delay_minutes' => 'nullable|integer|min:0|max:60',
        ]);

        // Vérifier que la résidence appartient à l'utilisateur
        if (!empty($validated['residence_id'])) {
            $residence = Residence::findOrFail($validated['residence_id']);
            if ($residence->owner_id !== Auth::id()) {
                abort(403);
            }
        }

        $autoReply->update([
            'name' => $validated['name'],
            'residence_id' => $validated['residence_id'] ?? null,
            'trigger_type' => $validated['trigger_type'],
            'trigger_conditions' => $validated['trigger_conditions'] ?? null,
            'message' => $validated['message'],
            'delay_minutes' => $validated['delay_minutes'] ?? 0,
        ]);

        return redirect()
            ->route('owner.auto-replies.index')
            ->with('success', 'Réponse automatique mise à jour.');
    }

    /**
     * Active/désactive une réponse
     */
    public function toggle(AutoReply $autoReply)
    {
        $this->authorize('update', $autoReply);

        $autoReply->update(['is_active' => !$autoReply->is_active]);

        $status = $autoReply->is_active ? 'activée' : 'désactivée';

        return back()->with('success', "Réponse {$status}.");
    }

    /**
     * Supprime une réponse
     */
    public function destroy(AutoReply $autoReply)
    {
        $this->authorize('delete', $autoReply);

        $autoReply->delete();

        return redirect()
            ->route('owner.auto-replies.index')
            ->with('success', 'Réponse automatique supprimée.');
    }

    /**
     * Utilise une réponse rapide (API)
     */
    public function use(Request $request, AutoReply $autoReply)
    {
        $this->authorize('view', $autoReply);

        if ($autoReply->trigger_type !== 'manual') {
            return response()->json(['error' => 'Cette réponse n\'est pas manuelle'], 400);
        }

        $variables = $request->validate([
            'guest_name' => 'nullable|string',
            'residence_name' => 'nullable|string',
        ]);

        $message = $autoReply->formatMessage($variables);
        $autoReply->markAsUsed();

        return response()->json([
            'message' => $message,
            'usage_count' => $autoReply->usage_count,
        ]);
    }
}
