<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreIcalFeedRequest;
use App\Models\IcalFeed;
use App\Services\IcalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class IcalController extends Controller
{
    public function __construct(
        private IcalService $icalService,
    ) {}

    public function index(Request $request): View
    {
        $user       = $request->user();
        $residences = $user->residences()->orderBy('name')->get();
        $feeds      = IcalFeed::whereIn('residence_id', $residences->pluck('id'))
            ->with('residence')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('owner.ical.index', compact('feeds', 'residences'));
    }

    public function store(StoreIcalFeedRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $feed = IcalFeed::create($data);

        // Synchroniser immédiatement
        $this->icalService->importFeed($feed);

        return redirect()->route('owner.ical.index')
            ->with('success', 'Flux iCal ajouté et synchronisé.');
    }

    public function sync(IcalFeed $feed): RedirectResponse
    {
        $imported = $this->icalService->importFeed($feed);

        return back()->with('success', "$imported événement(s) importé(s).");
    }

    public function destroy(IcalFeed $feed): RedirectResponse
    {
        $feed->blockedDates()->delete();
        $feed->delete();

        return redirect()->route('owner.ical.index')
            ->with('success', 'Flux iCal supprimé.');
    }

    /**
     * Export iCal public pour une résidence
     */
    public function export(string $token): Response
    {
        $feed = IcalFeed::where('export_token', $token)->firstOrFail();
        $residence = $feed->residence;

        $content = $this->icalService->generateExport($residence);

        return response($content, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . str_replace(' ', '-', $residence->name) . '.ics"',
        ]);
    }
}
