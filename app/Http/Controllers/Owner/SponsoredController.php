<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Services\JekoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SponsoredController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', SponsoredListing::class);
        $residences = Auth::user()->residences()->select('id', 'name')->get();

        $query = SponsoredListing::with('residence:id,name')
            ->where('user_id', Auth::id());

        if ($request->filled('residence_id')) {
            $query->where('residence_id', $request->residence_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sponsoredListings = $query->orderByDesc('created_at')->paginate(15);

        // Statistiques
        $stats = [
            'total' => SponsoredListing::where('user_id', Auth::id())->count(),
            'active' => SponsoredListing::where('user_id', Auth::id())->active()->count(),
            'total_spent' => SponsoredListing::where('user_id', Auth::id())->sum('amount_spent'),
            'total_impressions' => SponsoredListing::where('user_id', Auth::id())->sum('impressions'),
            'total_clicks' => SponsoredListing::where('user_id', Auth::id())->sum('clicks'),
            'total_contacts' => SponsoredListing::where('user_id', Auth::id())->sum('contacts_generated'),
        ];

        // Calcul du CTR global
        $stats['ctr'] = $stats['total_impressions'] > 0
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2)
            : 0;

        return view('owner.marketing.sponsored.index', compact('sponsoredListings', 'residences', 'stats'));
    }

    public function create()
    {
        $residences = Auth::user()->residences()->approved()->get();

        // Packages disponibles
        $packages = $this->getPackages();

        return view('owner.marketing.sponsored.create', compact('residences', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'type' => 'required|in:featured_home,top_search,highlighted,premium_listing',
            'duration' => 'required|in:7,14,30',
            'daily_budget' => 'nullable|numeric|min:500',
            'total_budget' => 'nullable|numeric|min:5000',
            'target_communes' => 'nullable|array',
        ]);

        // Vérifier la limite d'abonnement
        $user = Auth::user();
        $plan = $user->currentPlan();
        $maxSponsored = $plan ? ($plan->max_sponsored_per_month ?? 0) : 0;

        if ($maxSponsored === 0) {
            return back()->with('error', 'Votre abonnement ne permet pas de créer des mises en avant. Passez à un plan supérieur.')
                ->withInput();
        }

        $currentMonthCount = SponsoredListing::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($currentMonthCount >= $maxSponsored) {
            return back()->with('error', "Vous avez atteint votre limite de {$maxSponsored} mises en avant par mois. Passez à un plan supérieur pour en créer davantage.")
                ->withInput();
        }

        // Vérifier que la résidence appartient au propriétaire
        $residence = Residence::where('id', $validated['residence_id'])
            ->where('owner_id', Auth::id())
            ->firstOrFail();

        // Récupérer les infos du package
        $packages = $this->getPackages();
        $package = $packages[$validated['type']] ?? $packages['highlighted'];

        // Ne PAS définir starts_at/ends_at maintenant — sera défini après paiement
        $sponsoredListing = SponsoredListing::create([
            'residence_id' => $validated['residence_id'],
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'starts_at' => null,
            'ends_at' => null,
            'duration_days' => (int) $validated['duration'],
            'daily_budget' => $validated['daily_budget'] ?? null,
            'total_budget' => $validated['total_budget'] ?? $package['price'] * ($validated['duration'] / 7),
            'amount_spent' => 0,
            'billing_type' => $package['billing_type'],
            'cost_per_unit' => $package['cost_per_unit'],
            'impressions' => 0,
            'clicks' => 0,
            'contacts_generated' => 0,
            'target_communes' => $validated['target_communes'] ?? null,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        return redirect()->route('owner.marketing.sponsored.payment', $sponsoredListing)
            ->with('info', 'Procédez au paiement pour activer la mise en avant.');
    }

    public function show(SponsoredListing $sponsored)
    {
        Gate::authorize('view', $sponsored);

        $sponsored->load('residence');

        // Données pour les graphiques de performance
        $performanceData = $this->getPerformanceData($sponsored);

        return view('owner.marketing.sponsored.show', compact('sponsored', 'performanceData'));
    }

    public function payment(SponsoredListing $sponsored)
    {
        Gate::authorize('update', $sponsored);

        if ($sponsored->is_paid) {
            return redirect()->route('owner.marketing.sponsored.show', $sponsored);
        }

        $sponsored->load('residence');

        $paymentMethods = JekoPaymentService::paymentMethods();
        $jekoEnabled = app(JekoPaymentService::class)->isEnabled();

        return view('owner.marketing.sponsored.payment', compact('sponsored', 'paymentMethods', 'jekoEnabled'));
    }

    public function confirmPayment(Request $request, SponsoredListing $sponsored)
    {
        Gate::authorize('update', $sponsored);

        if ($sponsored->is_paid) {
            return redirect()->route('owner.marketing.sponsored.show', $sponsored)
                ->with('info', 'Cette campagne est déjà payée.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:wave,orange,mtn,moov,djamo',
        ]);

        $jekoService = app(JekoPaymentService::class);

        if (! $jekoService->isEnabled()) {
            return back()->with('error', 'Le service de paiement est temporairement indisponible.');
        }

        // Create Jeko payment request
        $result = $jekoService->createPaymentRequest($sponsored, $validated['payment_method']);

        if (! $result['success']) {
            return back()->with('error', $result['error']);
        }

        // Store Jeko payment info on the sponsored listing
        $sponsored->update([
            'jeko_payment_id' => $result['payment_id'],
            'jeko_reference' => $result['reference'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'processing',
        ]);

        // Redirect user to Jeko payment page
        if (! empty($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        return back()->with('error', 'Impossible d\'obtenir le lien de paiement. Veuillez réessayer.');
    }

    public function pause(SponsoredListing $sponsored)
    {
        Gate::authorize('pause', $sponsored);

        if ($sponsored->status !== 'active') {
            return back()->with('error', 'Cette mise en avant ne peut pas être mise en pause.');
        }

        $sponsored->pause();

        return back()->with('success', 'Mise en avant mise en pause.');
    }

    public function resume(SponsoredListing $sponsored)
    {
        Gate::authorize('resume', $sponsored);

        if ($sponsored->status !== 'paused') {
            return back()->with('error', 'Cette mise en avant ne peut pas être reprise.');
        }

        if (!$sponsored->is_paid) {
            return back()->with('error', 'Veuillez d\'abord effectuer le paiement.');
        }

        $sponsored->activate();

        return back()->with('success', 'Mise en avant reprise.');
    }

    public function cancel(SponsoredListing $sponsored)
    {
        Gate::authorize('cancel', $sponsored);

        if (in_array($sponsored->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cette mise en avant ne peut pas être annulée.');
        }

        $sponsored->cancel();

        return back()->with('success', 'Mise en avant annulée.');
    }

    private function getPackages(): array
    {
        $costPerClick = config('rezi.sponsored.cost_per_click', 50);
        $costPerView = config('rezi.sponsored.cost_per_view', 5);

        return [
            'featured_home' => [
                'name' => 'Page d\'accueil',
                'description' => 'Votre résidence apparaît en vedette sur la page d\'accueil',
                'price' => config('rezi.sponsored.featured_home_price_weekly', 25000),
                'billing_type' => 'flat_rate',
                'cost_per_unit' => 0,
                'features' => [
                    'Visibilité maximale',
                    'Badge "À la une"',
                    'Position premium',
                ],
            ],
            'top_search' => [
                'name' => 'Top Recherche',
                'description' => 'Apparaissez en tête des résultats de recherche',
                'price' => config('rezi.sponsored.top_search_price_weekly', 15000),
                'billing_type' => 'per_click',
                'cost_per_unit' => $costPerClick,
                'features' => [
                    'Top 3 des résultats',
                    'Badge "Sponsorisé"',
                    'Paiement au clic',
                ],
            ],
            'highlighted' => [
                'name' => 'Mis en avant',
                'description' => 'Design différencié dans les listes',
                'price' => config('rezi.sponsored.highlighted_price_weekly', 7500),
                'billing_type' => 'flat_rate',
                'cost_per_unit' => 0,
                'features' => [
                    'Bordure colorée',
                    'Badge visible',
                    'Économique',
                ],
            ],
            'premium_listing' => [
                'name' => 'Premium',
                'description' => 'Le pack complet pour un maximum de visibilité',
                'price' => config('rezi.sponsored.premium_price_weekly', 35000),
                'billing_type' => 'flat_rate',
                'cost_per_unit' => 0,
                'features' => [
                    'Page d\'accueil + Top recherche',
                    'Badge premium doré',
                    'Statistiques détaillées',
                    'Support prioritaire',
                ],
            ],
        ];
    }

    private function getPerformanceData(SponsoredListing $sponsored): array
    {
        // Données de performance réelles depuis la table sponsored_listing_stats
        $stats = $sponsored->dailyStats()
            ->where('date', '>=', now()->subDays(6)->toDateString())
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($stat) => $stat->date->format('d/m'));

        $days = [];
        $impressions = [];
        $clicks = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('d/m');
            $days[] = $key;

            $dayStat = $stats->get($key);
            $impressions[] = $dayStat ? $dayStat->impressions : 0;
            $clicks[] = $dayStat ? $dayStat->clicks : 0;
        }

        return [
            'labels' => $days,
            'impressions' => $impressions,
            'clicks' => $clicks,
        ];
    }
}
