<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageTemplateController extends Controller
{
    /**
     * Liste des templates
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Templates utilisateur + templates système
        $userTemplates = MessageTemplate::where('user_id', $user->id)
            ->orderByDesc('usage_count')
            ->get();

        $systemTemplates = MessageTemplate::system()
            ->active()
            ->get();

        $categories = MessageTemplate::getCategories();

        return view('templates.index', compact('userTemplates', 'systemTemplates', 'categories'));
    }

    /**
     * Formulaire de création
     */
    public function create(): View
    {
        $categories = MessageTemplate::getCategories();

        return view('templates.create', compact('categories'));
    }

    /**
     * Enregistrer un nouveau template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'content' => 'required|string|max:2000',
            'category' => 'required|string|in:'.implode(',', array_keys(MessageTemplate::getCategories())),
            'shortcut' => 'nullable|string|max:20|regex:/^[a-z0-9_]+$/',
            'language' => 'nullable|string|max:5',
        ]);

        $user = $request->user();

        // Vérifier l'unicité du raccourci pour cet utilisateur
        if (isset($validated['shortcut'])) {
            $exists = MessageTemplate::where('user_id', $user->id)
                ->where('shortcut', $validated['shortcut'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Ce raccourci est déjà utilisé',
                ], 422);
            }
        }

        $template = MessageTemplate::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'shortcut' => $validated['shortcut'] ?? null,
            'variables' => $this->extractVariables($validated['content']),
            'language' => $validated['language'] ?? 'fr',
            'is_active' => true,
            'is_system' => false,
        ]);

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    /**
     * Afficher un template
     */
    public function show(Request $request, MessageTemplate $template): View
    {
        $user = $request->user();

        // Vérifier l'accès
        if (!$template->is_system && $template->user_id !== $user->id) {
            abort(403);
        }

        return view('templates.show', compact('template'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Request $request, MessageTemplate $template): View
    {
        $user = $request->user();

        if ($template->is_system || $template->user_id !== $user->id) {
            abort(403);
        }

        $categories = MessageTemplate::getCategories();

        return view('templates.edit', compact('template', 'categories'));
    }

    /**
     * Mettre à jour un template
     */
    public function update(Request $request, MessageTemplate $template): JsonResponse
    {
        $user = $request->user();

        if ($template->is_system || $template->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'content' => 'sometimes|string|max:2000',
            'category' => 'sometimes|string|in:'.implode(',', array_keys(MessageTemplate::getCategories())),
            'shortcut' => 'nullable|string|max:20|regex:/^[a-z0-9_]+$/',
            'is_active' => 'sometimes|boolean',
        ]);

        // Vérifier l'unicité du raccourci
        if (isset($validated['shortcut']) && $validated['shortcut'] !== $template->shortcut) {
            $exists = MessageTemplate::where('user_id', $user->id)
                ->where('shortcut', $validated['shortcut'])
                ->where('id', '!=', $template->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Ce raccourci est déjà utilisé',
                ], 422);
            }
        }

        // Mettre à jour les variables si le contenu change
        if (isset($validated['content'])) {
            $validated['variables'] = $this->extractVariables($validated['content']);
        }

        $template->update($validated);

        return response()->json([
            'success' => true,
            'template' => $template->fresh(),
        ]);
    }

    /**
     * Supprimer un template
     */
    public function destroy(Request $request, MessageTemplate $template): JsonResponse
    {
        $user = $request->user();

        if ($template->is_system || $template->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $template->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Prévisualiser un template avec des variables
     */
    public function preview(Request $request, MessageTemplate $template): JsonResponse
    {
        $user = $request->user();

        if (!$template->is_system && $template->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $variables = $request->get('variables', []);
        $content = $template->generateContent($variables);

        return response()->json([
            'success' => true,
            'content' => $content,
            'required_variables' => $template->extractVariables(),
        ]);
    }

    /**
     * Rechercher un template par raccourci
     */
    public function byShortcut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shortcut' => 'required|string|max:20',
        ]);

        $user = $request->user();

        $template = MessageTemplate::forUser($user)
            ->byShortcut($validated['shortcut'])
            ->first();

        if (!$template) {
            return response()->json(['error' => 'Template non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    /**
     * Dupliquer un template
     */
    public function duplicate(Request $request, MessageTemplate $template): JsonResponse
    {
        $user = $request->user();

        if (!$template->is_system && $template->user_id !== $user->id) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $newTemplate = $template->replicate();
        $newTemplate->user_id = $user->id;
        $newTemplate->name = $template->name.' (copie)';
        $newTemplate->shortcut = null;
        $newTemplate->usage_count = 0;
        $newTemplate->is_system = false;
        $newTemplate->save();

        return response()->json([
            'success' => true,
            'template' => $newTemplate,
        ]);
    }

    /**
     * Extraire les variables d'un contenu
     */
    protected function extractVariables(string $content): array
    {
        preg_match_all('/\{([a-zA-Z_]+)\}/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }
}
