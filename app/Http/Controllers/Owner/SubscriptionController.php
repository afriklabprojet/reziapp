<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\JekoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function __construct(
        protected JekoPaymentService $jekoService
    ) {}

    /**
     * Afficher les plans d'abonnement disponibles
     */
    public function index()
    {
        $user = Auth::user();
        $plans = SubscriptionPlan::active()->ordered()->get();
        $currentSubscription = $user->activeSubscription();

        return view('owner.subscriptions.index', compact('plans', 'currentSubscription'));
    }

    /**
     * Souscrire à un plan
     */
    public function subscribe(Request $request, SubscriptionPlan $plan)
    {
        $request->validate([
            'billing_period' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:wave,orange,mtn,moov,djamo',
        ]);

        $user = Auth::user();

        // Vérifier si l'utilisateur a déjà un abonnement actif
        $currentSubscription = $user->activeSubscription();
        if ($currentSubscription && $currentSubscription->subscription_plan_id === $plan->id) {
            return back()->with('error', 'Vous êtes déjà abonné à ce plan.');
        }

        // Calculer le prix selon la période
        $billingPeriod = $request->billing_period;
        $amount = $billingPeriod === 'yearly' 
            ? $plan->yearly_price 
            : $plan->monthly_price;

        // Créer l'abonnement (en attente de paiement)
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'billing_cycle' => $billingPeriod,
            'amount' => $amount,
            'current_period_start' => now(),
            'current_period_end' => $billingPeriod === 'yearly' 
                ? now()->addYear() 
                : now()->addMonth(),
        ]);

        // Créer le paiement
        $reference = 'REZI-SUB-' . $subscription->id . '-' . Str::random(8);
        $subscriptionPayment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'currency' => 'XOF',
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'status' => 'pending',
            'payment_provider' => 'jeko',
            'reference' => $reference,
        ]);

        // Initier le paiement Jeko
        $result = $this->jekoService->createSubscriptionPayment(
            $subscriptionPayment,
            $request->payment_method,
            $plan->name . ' - ' . ($billingPeriod === 'yearly' ? 'Annuel' : 'Mensuel')
        );

        if ($result['success']) {
            return redirect($result['redirect_url']);
        }

        // En cas d'erreur, supprimer l'abonnement créé
        $subscriptionPayment->delete();
        $subscription->delete();

        return back()->with('error', $result['error'] ?? 'Erreur lors de l\'initiation du paiement.');
    }

    /**
     * Changer de plan
     */
    public function changePlan(Request $request, SubscriptionPlan $plan)
    {
        $request->validate([
            'payment_method' => 'required|in:wave,orange,mtn,moov,djamo',
        ]);

        $user = Auth::user();
        $currentSubscription = $user->activeSubscription();

        if (!$currentSubscription) {
            return redirect()->route('owner.subscriptions.index')
                ->with('error', 'Vous n\'avez pas d\'abonnement actif.');
        }

        if ($currentSubscription->subscription_plan_id === $plan->id) {
            return back()->with('error', 'Vous êtes déjà abonné à ce plan.');
        }

        // Calculer le prorata ou la différence à payer
        $currentPlan = $currentSubscription->plan;
        $daysRemaining = now()->diffInDays($currentSubscription->current_period_end);
        $totalDays = $currentSubscription->current_period_start->diffInDays($currentSubscription->current_period_end);
        
        $currentValue = ($currentPlan->monthly_price / $totalDays) * $daysRemaining;
        $newValue = ($plan->monthly_price / $totalDays) * $daysRemaining;
        $difference = max(0, $newValue - $currentValue);

        if ($difference > 0) {
            // Créer un paiement pour la différence
            $reference = 'REZI-SUB-UPG-' . $currentSubscription->id . '-' . Str::random(8);
            $subscriptionPayment = SubscriptionPayment::create([
                'subscription_id' => $currentSubscription->id,
                'amount' => round($difference),
                'currency' => 'XOF',
                'period_start' => now(),
                'period_end' => $currentSubscription->current_period_end,
                'status' => 'pending',
                'payment_provider' => 'jeko',
                'reference' => $reference,
                'metadata' => [
                    'type' => 'upgrade',
                    'from_plan' => $currentPlan->id,
                    'to_plan' => $plan->id,
                ],
            ]);

            $result = $this->jekoService->createSubscriptionPayment(
                $subscriptionPayment,
                $request->payment_method,
                'Mise à niveau vers ' . $plan->name
            );

            if ($result['success']) {
                // Stocker le plan cible pour le traitement après paiement
                $currentSubscription->update([
                    'metadata' => array_merge($currentSubscription->metadata ?? [], [
                        'pending_plan_change' => $plan->id,
                    ]),
                ]);

                return redirect($result['redirect_url']);
            }

            $subscriptionPayment->delete();
            return back()->with('error', $result['error'] ?? 'Erreur lors du paiement.');
        }

        // Downgrade gratuit ou plan moins cher
        $currentSubscription->changePlan($plan);
        
        return redirect()->route('owner.subscriptions.index')
            ->with('success', 'Votre abonnement a été modifié avec succès.');
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return back()->with('error', 'Vous n\'avez pas d\'abonnement actif.');
        }

        $subscription->cancel($request->input('reason'));

        return redirect()->route('owner.subscriptions.index')
            ->with('success', 'Votre abonnement a été annulé. Il restera actif jusqu\'au ' . $subscription->current_period_end->format('d/m/Y'));
    }

    /**
     * Réactiver l'abonnement annulé
     */
    public function resume()
    {
        $user = Auth::user();
        $subscription = $user->subscriptions()
            ->where('status', 'cancelled')
            ->where('current_period_end', '>', now())
            ->first();

        if (!$subscription) {
            return back()->with('error', 'Aucun abonnement annulé à réactiver.');
        }

        $subscription->resume();

        return redirect()->route('owner.subscriptions.index')
            ->with('success', 'Votre abonnement a été réactivé.');
    }

    /**
     * Historique des paiements
     */
    public function history()
    {
        $user = Auth::user();
        $payments = SubscriptionPayment::whereHas('subscription', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('subscription.plan')->latest()->paginate(20);

        return view('owner.subscriptions.history', compact('payments'));
    }

    /**
     * Callback de succès Jeko
     */
    public function paymentSuccess(Request $request)
    {
        $subscriptionPayment = SubscriptionPayment::where('reference', $request->reference)->first();

        if (!$subscriptionPayment) {
            return redirect()->route('owner.subscriptions.index')
                ->with('error', 'Paiement non trouvé.');
        }

        // Le webhook gère la mise à jour, mais on vérifie le statut
        if ($subscriptionPayment->status === 'paid') {
            return redirect()->route('owner.subscriptions.index')
                ->with('success', 'Votre abonnement a été activé avec succès !');
        }

        // Attendre la confirmation webhook
        return redirect()->route('owner.subscriptions.index')
            ->with('info', 'Paiement en cours de traitement. Vous recevrez une confirmation.');
    }

    /**
     * Callback d'erreur Jeko
     */
    public function paymentError(Request $request)
    {
        return redirect()->route('owner.subscriptions.index')
            ->with('error', 'Le paiement a échoué. Veuillez réessayer.');
    }
}
