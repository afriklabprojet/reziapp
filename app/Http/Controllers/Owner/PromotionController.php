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
        $this->authorize('viewAny', Promotion::class);

        $ownerId = Auth::id();
        $residences = Auth::user()->residences()->select('id', 'name')->get();

        $base = fn () => Promotion::whereHas('residence', fn ($q) => $q->where('owner_id', $ownerId));

        $query = $base()->with('residence:id,name');

        if ($request->filled('residence_id')) {
            $query->where('residence_id', $request->residence_id);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'active'   => $query->active(),
                'inactive' => $query->where('is_active', false)->where('ends_at', '>=', now()),
                'expired'  => $query->where('ends_at', '<', now()),
                default    => null,
            };
        }

        $promotions = $query->orderByDesc('created_at')->paginate(15);

        $stats = [
            'total'         => $base()->count(),
            'active'        => $base()->active()->count(),
            'expired'       => $base()->where('ends_at', '<', now())->count(),
            'inactive'      => $base()->where('is_active', false)->where('ends_at', '>=', now())->count(),
            'expiring_soon' => $base()->active()->where('ends_at', '<=', now()->addDays(7))->count(),
            'total_uses'    => (int) $base()->sum('uses_count'),
        ];

        return view('owner.marketing.promotions.index', compact('promotions', 'residences', 'stats'));
    }

    public function create()
    {
        $this->authorize('create', Promotion::class);

        $residences = Auth::user()->residences()->approved()->get();

        return view('owner.marketing.promotions.create', compact('residences'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Promotion::class);

        $validated = $request->validate([
            'residence_id'   => 'required|exists:residences,id',
            'title'          => 'required|string|max:255',
            'discount_type'  => 'required|in:percentage,fixed,free_nights',
            'discount_value' => 'required|numeric|min:1',
            'free_nights_min' => 'nullable|integer|min:1',
            'min_nights'     => 'nullable|integer|min:1',
            'max_uses'       => 'nullable|integer|min:1',
            'starts_at'      => 'required|date|after_or_equal:today',
            'ends_at'        => 'required|date|after:starts_at',
            'booking_start'  => 'nullable|date',
            'booking_end'    => 'nullable|date|after_or_equal:booking_start',
            'is_featured'    => 'boolean',
            'description'    => 'nullable|string|max:500',
        ]);

        // La résidence doit appartenir au propriétaire connecté
        Residence::where('id', $validated['residence_id'])
            ->where('owner_id', Auth::id())
            ->firstOrFail();

        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 90) {
            return back()->withErrors(['discount_value' => 'Le pourcentage ne peut pas dépasser 90%']);
        }

        $validated['user_id']    = Auth::id();
        $validated['is_active']  = true;
        $validated['is_featured'] = $request->boolean('is_featured');

        Promotion::create($validated);

        return redirect()->route('owner.marketing.promotions.index')
            ->with('success', 'Promotion créée avec succès !');
    }

    public function edit(Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $residences = Auth::user()->residences()->approved()->get();

        return view('owner.marketing.promotions.edit', compact('promotion', 'residences'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'discount_type'  => 'required|in:percentage,fixed,free_nights',
            'discount_value' => 'required|numeric|min:1',
            'free_nights_min' => 'nullable|integer|min:1',
            'min_nights'     => 'nullable|integer|min:1',
            'max_uses'       => 'nullable|integer|min:1',
            'starts_at'      => 'required|date',
            'ends_at'        => 'required|date|after:starts_at',
            'booking_start'  => 'nullable|date',
            'booking_end'    => 'nullable|date|after_or_equal:booking_start',
            'is_featured'    => 'boolean',
            'is_active'      => 'boolean',
            'description'    => 'nullable|string|max:500',
        ]);

        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 90) {
            return back()->withErrors(['discount_value' => 'Le pourcentage ne peut pas dépasser 90%']);
        }

        $validated['is_active']   = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        $promotion->update($validated);

        return redirect()->route('owner.marketing.promotions.index')
            ->with('success', 'Promotion mise à jour avec succès !');
    }

    public function destroy(Promotion $promotion)
    {
        $this->authorize('delete', $promotion);

        $promotion->delete();

        return redirect()->route('owner.marketing.promotions.index')
            ->with('success', 'Promotion supprimée avec succès !');
    }

    public function toggle(Promotion $promotion)
    {
        $this->authorize('toggle', $promotion);

        $promotion->update(['is_active' => ! $promotion->is_active]);

        $status = $promotion->fresh()->is_active ? 'activée' : 'désactivée';

        return back()->with('success', "Promotion {$status} avec succès !");
    }
}
