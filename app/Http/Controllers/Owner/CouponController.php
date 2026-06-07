<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Residence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $residences = Auth::user()->residences()->select('id', 'name')->get();

        $query = Coupon::with(['residence:id,name', 'uses'])
            ->where('user_id', Auth::id());

        if ($request->filled('residence_id')) {
            if ($request->residence_id === 'global') {
                $query->whereNull('residence_id');
            } else {
                $query->where('residence_id', $request->residence_id);
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<', now());
            } elseif ($request->status === 'exhausted') {
                $query->whereColumn('uses_count', '>=', 'max_uses');
            }
        }

        $coupons = $query->orderByDesc('created_at')->paginate(15);

        // Statistiques
        $stats = [
            'total' => Coupon::where('user_id', Auth::id())->count(),
            'active' => Coupon::where('user_id', Auth::id())->active()->count(),
            'total_uses' => Coupon::where('user_id', Auth::id())->sum('uses_count'),
            'total_discount' => Coupon::where('coupons.user_id', Auth::id())
                ->join('coupon_uses', 'coupons.id', '=', 'coupon_uses.coupon_id')
                ->sum('coupon_uses.discount_applied'),
        ];

        return view('owner.marketing.coupons.index', compact('coupons', 'residences', 'stats'));
    }

    public function create()
    {
        $residences = Auth::user()->residences()->approved()->get();

        return view('owner.marketing.coupons.create', compact('residences'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'residence_id' => 'nullable|exists:residences,id',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'description' => 'nullable|string|max:500',
        ]);

        // Vérifier que la résidence appartient au propriétaire (si fournie)
        if ($validated['residence_id']) {
            Residence::where('id', $validated['residence_id'])
                ->where('owner_id', Auth::id())
                ->firstOrFail();
        }

        // Générer un code si non fourni
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode();
        }

        // Validation supplémentaire pour le pourcentage
        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 90) {
            return back()->withErrors(['discount_value' => 'Le pourcentage ne peut pas dépasser 90%']);
        }

        $validated['user_id'] = Auth::id();
        $validated['is_active'] = true;
        $validated['name'] = $validated['description'] ?? $validated['code'] ?? 'Code promo';

        Coupon::create($validated);

        return redirect()->route('owner.marketing.coupons.index')
            ->with('success', 'Code promo créé avec succès !');
    }

    public function show(Coupon $coupon)
    {
        if ($coupon->user_id !== Auth::id()) {
            abort(403);
        }

        $coupon->load(['residence', 'uses.user']);

        return view('owner.marketing.coupons.show', compact('coupon'));
    }

    public function edit(Coupon $coupon)
    {
        if ($coupon->user_id !== Auth::id()) {
            abort(403);
        }

        $residences = Auth::user()->residences()->approved()->get();

        return view('owner.marketing.coupons.edit', compact('coupon', 'residences'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        if ($coupon->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,'.$coupon->id,
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:1',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 90) {
            return back()->withErrors(['discount_value' => 'Le pourcentage ne peut pas dépasser 90%']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $coupon->update($validated);

        return redirect()->route('owner.marketing.coupons.index')
            ->with('success', 'Code promo mis à jour avec succès !');
    }

    public function destroy(Coupon $coupon)
    {
        if ($coupon->user_id !== Auth::id()) {
            abort(403);
        }

        // Supprimer les utilisations associées
        $coupon->uses()->delete();
        $coupon->delete();

        return redirect()->route('owner.marketing.coupons.index')
            ->with('success', 'Code promo supprimé avec succès !');
    }

    public function toggle(Coupon $coupon)
    {
        if ($coupon->user_id !== Auth::id()) {
            abort(403);
        }

        $coupon->update(['is_active' => !$coupon->is_active]);

        $status = $coupon->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Code promo {$status} avec succès !");
    }

    public function duplicate(Coupon $coupon)
    {
        if ($coupon->user_id !== Auth::id()) {
            abort(403);
        }

        $newCoupon = $coupon->replicate();
        $newCoupon->code = $this->generateUniqueCode();
        $newCoupon->uses_count = 0;
        $newCoupon->created_at = now();
        $newCoupon->save();

        return redirect()->route('owner.marketing.coupons.edit', $newCoupon)
            ->with('success', 'Code promo dupliqué avec succès !');
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'Rezi App'.Str::upper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
