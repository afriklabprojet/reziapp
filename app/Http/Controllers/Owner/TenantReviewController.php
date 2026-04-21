<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreTenantReviewRequest;
use App\Models\TenantReview;
use App\Services\TenantReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantReviewController extends Controller
{
    public function __construct(
        private TenantReviewService $reviewService,
    ) {
    }

    public function index(Request $request): View
    {
        $user      = $request->user();
        $reviews   = $this->reviewService->getReviews($user, $request->only(['residence_id']));
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.tenant-reviews.index', compact('reviews', 'residences'));
    }

    public function create(Request $request): View
    {
        $residences = $request->user()->residences()->orderBy('name')->get();

        return view('owner.tenant-reviews.create', compact('residences'));
    }

    public function store(StoreTenantReviewRequest $request): RedirectResponse
    {
        $this->reviewService->create($request->user(), $request->validated());

        return redirect()->route('owner.tenant-reviews.index')
            ->with('success', 'Avis sur le locataire enregistré.');
    }

    public function show(TenantReview $tenantReview): View
    {
        $tenantReview->load(['tenant', 'residence', 'booking']);
        $tenantScore = $this->reviewService->getTenantScore($tenantReview->tenant_id);

        return view('owner.tenant-reviews.show', compact('tenantReview', 'tenantScore'));
    }

    public function destroy(TenantReview $tenantReview): RedirectResponse
    {
        abort_unless($tenantReview->owner_id === auth()->id(), 403);
        $this->reviewService->delete($tenantReview);

        return redirect()->route('owner.tenant-reviews.index')
            ->with('success', 'Avis supprimé.');
    }
}
