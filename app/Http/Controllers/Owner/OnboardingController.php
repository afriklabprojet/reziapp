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
        $progress = $this->onboardingService->getProgress($request->user());

        return view('owner.onboarding.index', compact('progress'));
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
