<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints AJAX pour l'assistant IA côté propriétaire.
 */
class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $ai,
    ) {}

    /**
     * Générer une description d'annonce.
     */
    public function generateDescription(Request $request): JsonResponse
    {
        if (! $this->ai->isAvailable()) {
            return response()->json(['error' => 'Service IA non disponible. Clé API manquante.'], 503);
        }

        $request->validate([
            'type'          => ['nullable', 'string'],
            'type_location' => ['nullable', 'string'],
            'commune'       => ['nullable', 'string'],
            'quartier'      => ['nullable', 'string'],
            'bedrooms'      => ['nullable', 'integer'],
            'bathrooms'     => ['nullable', 'integer'],
            'surface_area'  => ['nullable', 'numeric'],
            'price'         => ['nullable', 'numeric'],
            'name'          => ['nullable', 'string', 'max:100'],
            'amenities'     => ['nullable', 'array'],
        ]);

        try {
            $description = $this->ai->generateListingDescription($request->all());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erreur du service IA. Réessayez plus tard.'], 422);
        }

        if (! $description) {
            return response()->json(['error' => 'Impossible de générer la description. Réessayez.'], 422);
        }

        return response()->json(['description' => $description]);
    }

    /**
     * Générer un titre d'annonce.
     */
    public function generateTitle(Request $request): JsonResponse
    {
        if (! $this->ai->isAvailable()) {
            return response()->json(['error' => 'Service IA non disponible. Clé API manquante.'], 503);
        }

        $request->validate([
            'type'          => ['nullable', 'string'],
            'type_location' => ['nullable', 'string'],
            'commune'       => ['nullable', 'string'],
            'quartier'      => ['nullable', 'string'],
            'bedrooms'      => ['nullable', 'integer'],
        ]);

        try {
            $title = $this->ai->generateListingTitle($request->all());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erreur du service IA. Réessayez plus tard.'], 422);
        }

        if (! $title) {
            return response()->json(['error' => 'Impossible de générer le titre. Réessayez.'], 422);
        }

        return response()->json(['title' => $title]);
    }

    /**
     * Améliorer une description existante.
     */
    public function improveDescription(Request $request): JsonResponse
    {
        if (! $this->ai->isAvailable()) {
            return response()->json(['error' => 'Service IA non disponible. Clé API manquante.'], 503);
        }

        $request->validate([
            'description' => ['required', 'string', 'min:20'],
            'type'        => ['nullable', 'string'],
            'commune'     => ['nullable', 'string'],
            'quartier'    => ['nullable', 'string'],
        ]);

        try {
            $improved = $this->ai->improveListingDescription(
                $request->description,
                $request->only(['type', 'commune', 'quartier']),
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erreur du service IA. Réessayez plus tard.'], 422);
        }

        if (! $improved) {
            return response()->json(['error' => 'Impossible d\'améliorer la description. Réessayez.'], 422);
        }

        return response()->json(['description' => $improved]);
    }

    /**
     * Générer des clauses pour un contrat.
     */
    public function generateClauses(Request $request): JsonResponse
    {
        if (! $this->ai->isAvailable()) {
            return response()->json(['error' => 'Service IA non disponible. Clé API manquante.'], 503);
        }

        $request->validate([
            'lease_type'         => ['required', 'string'],
            'monthly_rent'       => ['nullable', 'numeric'],
            'deposit_amount'     => ['nullable', 'numeric'],
            'residence_name'     => ['nullable', 'string'],
            'commune'            => ['nullable', 'string'],
            'included_services'  => ['nullable', 'array'],
        ]);

        try {
            $clauses = $this->ai->generateContractClauses($request->all());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erreur du service IA. Réessayez plus tard.'], 422);
        }

        if (! $clauses) {
            return response()->json(['error' => 'Impossible de générer les clauses. Réessayez.'], 422);
        }

        return response()->json(['clauses' => $clauses]);
    }

    /**
     * Suggérer des services pour un contrat.
     */
    public function suggestServices(Request $request): JsonResponse
    {
        if (! $this->ai->isAvailable()) {
            return response()->json(['error' => 'Service IA non disponible. Clé API manquante.'], 503);
        }

        $request->validate([
            'lease_type'   => ['nullable', 'string'],
            'monthly_rent' => ['nullable', 'numeric'],
            'commune'      => ['nullable', 'string'],
        ]);

        try {
            $services = $this->ai->suggestContractServices($request->all());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erreur du service IA. Réessayez plus tard.'], 422);
        }

        if (! $services) {
            return response()->json(['error' => 'Impossible de suggérer des services. Réessayez.'], 422);
        }

        return response()->json(['services' => $services]);
    }
}
