<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        do {
            $code = strtoupper(substr($user->name, 0, 3)).strtoupper(Str::random(5));
            $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $code;
    }

    public function processReferral(User $newUser, string $referralCode): ?Referral
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (! $referrer || $referrer->id === $newUser->id) {
            return null;
        }

        if (Referral::where('referred_id', $newUser->id)->exists()) {
            return null;
        }

        $config = config('rezi.referral');

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $newUser->id,
            'status' => 'pending',
            'referrer_reward' => $config['referrer_reward'] ?? 5000,
            'referred_reward' => $config['referred_reward'] ?? 2500,
            'reward_type' => 'credit',
        ]);

        $referrer->notify(new ReferralCreated($referral));

        return $referral;
    }

    public function qualifyReferral(User $user): ?Referral
    {
        $referral = Referral::where('referred_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $referral) {
            return null;
        }

        $referral->update([
            'status' => 'qualified',
            'qualified_at' => now(),
        ]);

        return $referral;
    }

    public function rewardReferral(Referral $referral): bool
    {
        if ($referral->status !== 'qualified') {
            return false;
        }

        return DB::transaction(function () use ($referral): bool {
            $locked = Referral::query()->lockForUpdate()->find($referral->id);

            if (! $locked || $locked->status !== 'qualified') {
                return false;
            }

            // Verrouiller les deux users dans un ordre déterministe (id croissant)
            // pour prévenir les deadlocks sur récompenses simultanées.
            $referrerId = $locked->referrer_id;
            $referredId = $locked->referred_id;

            $userIds = collect([$referrerId, $referredId])->sort()->values()->all();
            User::whereIn('id', $userIds)->orderBy('id')->lockForUpdate()->get();

            User::where('id', $referrerId)->increment('referral_balance', $locked->referrer_reward);
            User::where('id', $referredId)->increment('referral_balance', $locked->referred_reward);

            $locked->update([
                'status' => 'rewarded',
                'rewarded_at' => now(),
            ]);

            return true;
        });
    }

    public function getReferralLeaderboard(int $limit = 10)
    {
        return User::select('users.*')
            ->selectRaw('COUNT(referrals.id) as referrals_count')
            ->selectRaw('SUM(CASE WHEN referrals.status = "rewarded" THEN referrals.referrer_reward ELSE 0 END) as total_earned')
            ->leftJoin('referrals', 'users.id', '=', 'referrals.referrer_id')
            ->groupBy('users.id')
            ->having('referrals_count', '>', 0)
            ->orderByDesc('referrals_count')
            ->limit($limit)
            ->get();
    }
}
