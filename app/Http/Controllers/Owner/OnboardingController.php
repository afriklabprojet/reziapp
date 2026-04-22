<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService,
    ) {
    }

    public function index(Request $request): View
    {
        $data = $this->onboardingService->getProgress($request->user());

        return view('owner.onboarding.index', [
            'progress' => $data['percentage'],
            'steps'    => array_map(fn ($s) => [
                'title'        => $s['label'],
                'description'  => $s['description'],
                'completed'    => $s['completed'],
                'action_url'   => $this->getStepUrl($s['key']),
                'action_label' => $this->getStepLabel($s['key']),
            ], $data['steps']),
        ]);
    }

    private function getStepUrl(string $key): ?string
    {
        return match ($key) {
            'profile_completed'    => route('profile.edit'),
            'identity_verified'    => route('owner.documents.index'),
            'first_listing'        => route('owner.residences.create'),
            'pricing_configured'   => route('owner.residences.index'),
            'instant_book_enabled' => route('owner.residences.index'),
            'guidebook_created'    => route('owner.guidebooks.index'),
            default                => null,
        };
    }

    private function getStepLabel(string $key): string
    {
        return match ($key) {
            'profile_completed'    => 'Compléter le profil',
            'identity_verified'    => 'Vérifier mon identité',
            'first_listing'        => 'Publier ma résidence',
            'pricing_configured'   => 'Configurer les tarifs',
            'instant_book_enabled' => 'Activer la réservation',
            'guidebook_created'    => 'Créer un guide',
            default                => 'Compléter',
        };
    }

    public function markStep(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'step' => 'required|string',
        ]);

        $this->onboardingService->markStepCompleted($request->user(), $validated['step']);

        return back()->with('success', 'Étape marquée comme complétée.');
    }
}
