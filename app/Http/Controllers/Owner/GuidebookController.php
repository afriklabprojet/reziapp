<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreGuidebookRequest;
use App\Models\Guidebook;
use App\Models\GuidebookRecommendation;
use App\Models\GuidebookSection;
use App\Services\GuidebookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuidebookController extends Controller
{
    public function __construct(
        private GuidebookService $guidebookService,
    ) {}

    public function index(Request $request): View
    {
        $guidebooks = Guidebook::where('user_id', $request->user()->id)
            ->with('residence')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('owner.guidebooks.index', compact('guidebooks'));
    }

    public function create(): View
    {
        $residences = request()->user()->residences()->orderBy('name')->get();
        return view('owner.guidebooks.create', compact('residences'));
    }

    public function store(StoreGuidebookRequest $request): RedirectResponse
    {
        $guidebook = $this->guidebookService->create($request->user(), $request->validated());

        return redirect()->route('owner.guidebooks.show', $guidebook)
            ->with('success', 'Guide de bienvenue créé.');
    }

    public function show(Guidebook $guidebook): View
    {
        $guidebook->load('sections', 'recommendations', 'residence');
        return view('owner.guidebooks.show', compact('guidebook'));
    }

    public function edit(Guidebook $guidebook): View
    {
        $guidebook->load('sections', 'recommendations');
        $residences = request()->user()->residences()->orderBy('name')->get();

        return view('owner.guidebooks.edit', compact('guidebook', 'residences'));
    }

    public function update(StoreGuidebookRequest $request, Guidebook $guidebook): RedirectResponse
    {
        $this->guidebookService->update($guidebook, $request->validated());

        return redirect()->route('owner.guidebooks.show', $guidebook)
            ->with('success', 'Guide mis à jour.');
    }

    public function destroy(Guidebook $guidebook): RedirectResponse
    {
        $guidebook->delete();

        return redirect()->route('owner.guidebooks.index')
            ->with('success', 'Guide supprimé.');
    }

    public function addSection(Request $request, Guidebook $guidebook): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'icon'    => 'nullable|string|max:50',
        ]);

        $validated['sort_order'] = $guidebook->sections()->count() + 1;
        $guidebook->sections()->create($validated);

        return back()->with('success', 'Section ajoutée.');
    }

    public function removeSection(Guidebook $guidebook, GuidebookSection $section): RedirectResponse
    {
        $section->delete();
        return back()->with('success', 'Section supprimée.');
    }

    public function addRecommendation(Request $request, Guidebook $guidebook): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'category'    => 'required|in:' . implode(',', array_keys(GuidebookRecommendation::CATEGORIES)),
            'address'     => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'distance'    => 'nullable|string|max:100',
        ]);

        $this->guidebookService->addRecommendation($guidebook, $validated);

        return back()->with('success', 'Recommandation ajoutée.');
    }

    public function togglePublish(Guidebook $guidebook): RedirectResponse
    {
        if ($guidebook->is_published) {
            $this->guidebookService->unpublish($guidebook);
            $msg = 'Guide dépublié.';
        } else {
            $this->guidebookService->publish($guidebook);
            $msg = 'Guide publié.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Vue publique du guidebook (accessible via token)
     */
    public function publicShow(string $token): View
    {
        $guidebook = Guidebook::where('access_token', $token)
            ->where('is_published', true)
            ->with('sections', 'recommendations', 'residence')
            ->firstOrFail();

        return view('guidebooks.public', compact('guidebook'));
    }
}
