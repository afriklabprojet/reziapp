<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Générer un code de parrainage si pas existant
        if (!$user->referral_code) {
            $user->referral_code = $this->generateReferralCode();
            $user->save();
        }

        // Parrainages effectués
        $referrals = Referral::with('referred:id,name,email,created_at')
            ->where('referrer_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        // Statistiques
        $referralsQuery = Referral::where('referrer_id', $user->id);
        $stats = [
            'total_referrals' => (clone $referralsQuery)->count(),
            'pending' => (clone $referralsQuery)->where('status', 'pending')->count(),
            'qualified' => (clone $referralsQuery)->where('status', 'qualified')->count(),
            'rewarded' => (clone $referralsQuery)->where('status', 'rewarded')->count(),
            'total_rewards' => (clone $referralsQuery)->where('status', 'rewarded')->sum('referrer_reward'),
            'referral_balance' => $user->referral_balance ?? 0,
        ];

        // Configuration parrainage
        $referralConfig = [
            'referrer_reward' => config('rezi.referral.referrer_reward', 5000),
            'referred_reward' => config('rezi.referral.referred_reward', 2500),
            'referrer_reward_type' => config('rezi.referral.referrer_reward_type', 'credit'),
            'referred_reward_type' => config('rezi.referral.referred_reward_type', 'discount'),
        ];

        return view('owner.marketing.referrals.index', compact('referrals', 'stats', 'referralConfig'));
    }

    public function share(Request $request)
    {
        $user = Auth::user();
        $channel = $request->input('channel', 'copy');

        $referralUrl = route('register', ['ref' => $user->referral_code]);
        $message = "Rejoins Rezi Studio Meublé Faya et trouve ta résidence meublée idéale ! Utilise mon code {$user->referral_code} pour bénéficier d'une remise. {$referralUrl}";

        // Enregistrer l'action de partage (pour analytics)
        // ...

        switch ($channel) {
            case 'whatsapp':
                return redirect('https://wa.me/?text='.urlencode($message));
            case 'facebook':
                return redirect('https://www.facebook.com/sharer/sharer.php?u='.urlencode($referralUrl));
            case 'twitter':
                return redirect('https://twitter.com/intent/tweet?text='.urlencode($message));
            case 'email':
                return redirect('mailto:?subject=Rejoins Rezi Studio Meublé Faya !&body='.urlencode($message));
            default:
                return back()->with('referral_url', $referralUrl);
        }
    }

    public function claim(Referral $referral)
    {
        if ($referral->referrer_id !== Auth::id()) {
            abort(403);
        }

        if ($referral->status === 'rewarded') {
            return back()->with('error', 'Cette récompense a déjà été réclamée.');
        }

        if ($referral->status !== 'qualified') {
            return back()->with('error', 'Le parrainage n\'est pas encore qualifié.');
        }

        // Utiliser la méthode reward() du modèle qui crédite parrain + filleul
        $config = config('rezi.referral');
        $referral->reward(
            $referral->referrer_reward ?? $config['referrer_reward'] ?? 5000,
            $referral->referred_reward ?? $config['referred_reward'] ?? 2500,
            $referral->reward_type ?? 'credit',
        );

        // Notifier le parrain et le filleul que la récompense est créditée
        $referral->referrer->notify(new \App\Notifications\ReferralRewarded($referral, 'referrer'));
        if ($referral->referred) {
            $referral->referred->notify(new \App\Notifications\ReferralRewarded($referral, 'referred'));
        }

        $rewardAmount = $referral->referrer_reward;

        return back()->with('success', "Félicitations ! {$rewardAmount} FCFA ont été ajoutés à votre solde de parrainage.");
    }

    public function leaderboard()
    {
        $leaderboard = User::select('id', 'name')
            ->withCount(['referralsMade as completed_referrals' => function ($q) {
                $q->where('status', 'rewarded');
            }])
            ->having('completed_referrals', '>', 0)
            ->orderByDesc('completed_referrals')
            ->limit(20)
            ->get();

        $userRank = null;
        $user = Auth::user();
        $userCompletedCount = $user->referralsMade()->where('status', 'rewarded')->count();

        if ($userCompletedCount > 0) {
            $userRank = User::withCount(['referralsMade as completed_referrals' => function ($q) {
                $q->where('status', 'rewarded');
            }])
            ->having('completed_referrals', '>', $userCompletedCount)
            ->count() + 1;
        }

        return view('owner.marketing.referrals.leaderboard', compact('leaderboard', 'userRank', 'userCompletedCount'));
    }

    private function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
