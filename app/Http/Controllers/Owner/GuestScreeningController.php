<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GuestScore;
use App\Services\GuestScreeningService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestScreeningController extends Controller
{
    public function __construct(
        private GuestScreeningService $screeningService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // Voyageurs ayant réservé chez ce propriétaire
        $guestIds = \App\Models\Booking::whereHas('residence', fn ($q) => $q->where('owner_id', $user->id))
            ->pluck('user_id')
            ->unique();

        $scores = GuestScore::whereIn('user_id', $guestIds)
            ->with('user')
            ->orderByDesc('total_score')
            ->paginate(20);

        return view('owner.screening.index', compact('scores'));
    }

    public function show(GuestScore $score): View
    {
        $score->load('user');
        return view('owner.screening.show', compact('score'));
    }

    public function recalculate(GuestScore $score): \Illuminate\Http\RedirectResponse
    {
        $this->screeningService->calculateScore($score->user);

        return back()->with('success', 'Score recalculé.');
    }
}
