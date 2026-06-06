<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    /**
     * Afficher le modèle de commission propriétaire.
     */
    public function index(): View
    {
        $user = Auth::user();
        $commissionRate = PlatformSetting::getCommissionRate();
        $commissionRateDecimal = $commissionRate / 100;

        $completedBookingsQuery = Booking::forOwner($user->id)
            ->completed()
            ->where('payment_status', 'paid');

        $totalReservations = (clone $completedBookingsQuery)->count();
        $totalReservationVolume = (float) (clone $completedBookingsQuery)->sum('total_amount');
        $totalCommission = round($totalReservationVolume * $commissionRateDecimal, 0);
        $totalOwnerRevenue = $totalReservationVolume - $totalCommission;

        $recentBookings = (clone $completedBookingsQuery)
            ->with(['residence:id,name'])
            ->orderByDesc('paid_at')
            ->orderByDesc('check_out')
            ->limit(8)
            ->get()
            ->map(function (Booking $booking) use ($commissionRateDecimal) {
                $commissionAmount = round(((float) $booking->total_amount) * $commissionRateDecimal, 0);
                $booking->commission_amount = $commissionAmount;
                $booking->owner_net_amount = (float) $booking->total_amount - $commissionAmount;

                return $booking;
            });

        $exampleBookingAmount = 150000;
        $exampleCommissionAmount = round($exampleBookingAmount * $commissionRateDecimal, 0);

        return view('owner.subscriptions.index', compact(
            'commissionRate',
            'totalReservations',
            'totalReservationVolume',
            'totalCommission',
            'totalOwnerRevenue',
            'recentBookings',
            'exampleBookingAmount',
            'exampleCommissionAmount',
        ));
    }

    /**
     * Ancienne action d'abonnement désormais neutralisée.
     */
    public function subscribe(Request $request, SubscriptionPlan $plan)
    {
        return redirect()->route('owner.marketing.subscriptions.index')
            ->with('info', 'ReziApp ne fonctionne pas avec des abonnements. La plateforme prélève uniquement 10% sur le montant total de chaque réservation, côté propriétaire.');
    }

    /**
     * Ancienne action de changement de plan désormais neutralisée.
     */
    public function changePlan(Request $request, SubscriptionPlan $plan)
    {
        return redirect()->route('owner.marketing.subscriptions.index')
            ->with('info', 'Aucun changement de plan n\'est nécessaire: ReziApp applique un modèle unique de commission de 10% sur chaque réservation propriétaire.');
    }

    /**
     * Ancienne action d'annulation désormais neutralisée.
     */
    public function cancel(Request $request)
    {
        return redirect()->route('owner.marketing.subscriptions.index')
            ->with('info', 'Aucun abonnement à annuler: ReziApp facture uniquement une commission de 10% sur les réservations encaissées par le propriétaire.');
    }

    /**
     * Ancienne action de réactivation désormais neutralisée.
     */
    public function resume()
    {
        return redirect()->route('owner.marketing.subscriptions.index')
            ->with('info', 'ReziApp n\'utilise pas de réactivation d\'abonnement. Le modèle économique repose sur une commission propriétaire de 10% par réservation.');
    }

    /**
     * Historique des commissions calculées à partir des réservations payées.
     */
    public function history(): View
    {
        $user = Auth::user();
        $commissionRate = PlatformSetting::getCommissionRate();
        $commissionRateDecimal = $commissionRate / 100;

        $bookings = Booking::forOwner($user->id)
            ->completed()
            ->where('payment_status', 'paid')
            ->with(['residence:id,name'])
            ->orderByDesc('paid_at')
            ->orderByDesc('check_out')
            ->paginate(20);

        $bookings->getCollection()->transform(function (Booking $booking) use ($commissionRateDecimal) {
            $commissionAmount = round(((float) $booking->total_amount) * $commissionRateDecimal, 0);
            $booking->commission_amount = $commissionAmount;
            $booking->owner_net_amount = (float) $booking->total_amount - $commissionAmount;

            return $booking;
        });

        return view('owner.subscriptions.history', [
            'bookings' => $bookings,
            'commissionRate' => $commissionRate,
        ]);
    }

    /**
     * Ancien callback de succès désormais neutralisé.
     */
    public function paymentSuccess(Request $request)
    {
        return redirect()->route('owner.marketing.subscriptions.index')
            ->with('info', 'Aucun paiement d\'abonnement n\'est attendu. ReziApp prélève directement 10% sur le montant total de chaque réservation propriétaire.');
    }

    /**
     * Ancien callback d'erreur désormais neutralisé.
     */
    public function paymentError(Request $request)
    {
        return redirect()->route('owner.marketing.subscriptions.index')
            ->with('info', 'Cette page d\'erreur d\'abonnement n\'est plus utilisée. Le modèle ReziApp est une commission propriétaire de 10% par réservation.');
    }
}
