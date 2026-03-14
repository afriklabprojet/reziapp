<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Controller d'authentification API
 *
 * Gère login, register, logout via Sanctum
 */
class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['sometimes', 'in:user,owner'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'user',
            'phone' => $validated['phone'] ?? null,
        ]);

        Log::info('Nouvel utilisateur inscrit via API', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        // Créer un token Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            Log::warning('Tentative de connexion échouée', [
                'email' => $validated['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Révoquer les anciens tokens (sécurité)
        $user->tokens()->delete();

        // Créer un nouveau token
        $deviceName = $validated['device_name'] ?? 'api-token';
        $token = $user->createToken($deviceName)->plainTextToken;

        Log::info('Connexion réussie via API', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'profile_photo' => $user->profile_photo,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Déconnexion (révocation du token actuel)
     */
    public function logout(Request $request): JsonResponse
    {
        // Révoquer le token actuel
        $request->user()->currentAccessToken()->delete();

        Log::info('Déconnexion via API', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * Récupérer l'utilisateur authentifié
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'profile_photo' => $user->profile_photo,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Liste des utilisateurs (Admin uniquement)
     */
    public function users(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $safe = str_replace(['%', '_'], ['\%', '\_'], $search);
                $q->where('name', 'like', "%{$safe}%")
                  ->orWhere('email', 'like', "%{$safe}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Modifier le rôle d'un utilisateur (Admin uniquement)
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:user,owner,admin'],
        ]);

        // Empêcher un admin de se rétrograder lui-même
        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre rôle.',
            ], 403);
        }

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        Log::info('Rôle utilisateur modifié', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id,
            'old_role' => $oldRole,
            'new_role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle mis à jour.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }
}
