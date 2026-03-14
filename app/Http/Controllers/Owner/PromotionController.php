<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Residence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $residences = Auth::user()->residences()->select('id', 'name')->get();

        $query = Promotion::with('residence:id,name')
            ->whereHas('residence', fn ($q) => $q->where('owner_id', Auth::id()));

        if ($request->filled('residence_id')) {
            $query->where('residence_id', $request->residence_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('ends_at', '<', now());
            }
        }

        $promotions = $query->orderByDesc('created_at')->paginate(15);

        // Statistiques
        $stats = [
            'total' => Promotion::whereHas('residence', fn ($q) => $q->where('owner_id', Auth::id()))->count(),
            'active' => Promotion::whereHas('residence', fn ($q) => $q->where('owner_id', Auth::id()))->active()->count(),
            'total_uses' => Promotion::whereHas('residence', fn ($q) => $q->where('owner_id', Auth::id()))->sum('uses_count'),
        ];

        return view('owner.marketing.promotions.index', compact('promotions', 'residences', 'stats'));
    }

    public function create()
    {
        $residences = Auth::user()->residences()->approved()->get();

        return view('owner.marketing.promotions.create', compact('residences'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'title' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed,free_nights',
            'discount_value' => 'required|numeric|min:1',
            'free_nights_min' => 'nullable|integer|min:1',
            'min_nights' => 'nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'required|date|after_or_equal:today',
            'ends_at' => 'required|date|after:starts_at',
            'description' => 'nullable|string|max:500',
        ]);

        // Vérifier que la résidence appartient au propriétaire
        $residence = Residence::where('id', $validated['residence_id'])
            ->where('owner_id', Auth::id())
            ->firstOrFail();

        // Validation supplémentaire pour le pourcentage
        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 90) {
            return back()->withErrors(['discount_value' => 'Le pourcentage ne peut pas dépasser 90%']);
        }

        $validated['user_id'] = Auth::id();
        $validated['is_active'] = true;

        Promotion::create($validated);

        return redirect()->route('owner.marketing.promotions.index')
            ->with('success', 'Promotion créée avec succès !');
    }

    public function edit(Promotion $promotion)
    {
        // Vérifier que la promotion appartient au propriétaire
        if ($promotion->residence->owner_id !== Auth::id()) {
            abort(403);
        }

        $residences = Auth::user()->residences()->approved()->get();

        return view('owner.marketing.promotions.edit', compact('promotion', 'residences'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        if ($promotion->residence->owner_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed,free_nights',
            'discount_value' => 'required|numeric|min:1',
            'free_nights_min' => 'nullable|integer|min:1',
            'min_nights' => 'nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 90) {
            return back()->withErrors(['discount_value' => 'Le pourcentage ne peut pas dépasser 90%']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $promotion->update($validated);

        return redirect()->route('owner.marketing.promotions.index')
            ->with('success', 'Promotion mise à jour avec succès !');
    }

    public function destroy(Promotion $promotion)
    {
        if ($promotion->residence->owner_id !== Auth::id()) {
            abort(403);
        }

        $promotion->delete();

        return redirect()->route('owner.marketing.promotions.index')
            ->with('success', 'Promotion supprimée avec succès !');
    }

    public function toggle(Promotion $promotion)
    {
        if ($promotion->residence->owner_id !== Auth::id()) {
            abort(403);
        }

        $promotion->update(['is_active' => !$promotion->is_active]);

        $status = $promotion->is_active ? 'activée' : 'désactivée';

        return back()->with('success', "Promotion {$status} avec succès !");
    }
}
