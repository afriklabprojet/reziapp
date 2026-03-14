<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreDamageReportRequest;
use App\Models\DamageReport;
use App\Services\DamageReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DamageReportController extends Controller
{
    public function __construct(
        private DamageReportService $damageService,
    ) {}

    public function index(Request $request): View
    {
        $user       = $request->user();
        $reports    = $this->damageService->getReports($user, $request->only(['status', 'severity', 'residence_id']));
        $residences = $user->residences()->orderBy('name')->get();

        return view('owner.damages.index', compact('reports', 'residences'));
    }

    public function create(): View
    {
        $user       = request()->user();
        $residences = $user->residences()->orderBy('name')->get();
        $categories = DamageReport::CATEGORIES;
        $severities = DamageReport::SEVERITIES;

        return view('owner.damages.create', compact('residences', 'categories', 'severities'));
    }

    public function store(StoreDamageReportRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Upload photos
        if ($request->hasFile('photos')) {
            $photos = [];
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('damage-reports', 'public');
            }
            $data['photos'] = $photos;
        }

        $report = $this->damageService->create($request->user(), $data);

        return redirect()->route('owner.damages.show', $report)
            ->with('success', 'Rapport de dommage créé.');
    }

    public function show(DamageReport $damage): View
    {
        $damage->load('residence', 'booking', 'reporter');
        return view('owner.damages.show', compact('damage'));
    }

    public function updateStatus(Request $request, DamageReport $damage): RedirectResponse
    {
        $validated = $request->validate([
            'status'      => 'required|in:assessed,repair_scheduled,repaired,insurance_claimed,closed',
            'actual_cost' => 'nullable|numeric|min:0',
        ]);

        $this->damageService->updateStatus($damage, $validated['status'], $validated);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy(DamageReport $damage): RedirectResponse
    {
        // Supprimer les photos
        foreach ($damage->photos ?? [] as $photo) {
            Storage::disk('public')->delete($photo);
        }

        $damage->delete();

        return redirect()->route('owner.damages.index')
            ->with('success', 'Rapport supprimé.');
    }
}
