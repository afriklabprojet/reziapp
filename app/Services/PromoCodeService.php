<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\PromoCodeUse;
use App\Models\User;
use Illuminate\Support\Str;

class PromoCodeService
{
    /**
     * Valider un code promo
     */
    public function validate(
        string $code,
        int $residenceId,
        float $subtotal,
        int $nights,
        int $userId,
    ): array {
        $promoCode = PromoCode::byCode($code)->first();

        if (!$promoCode) {
            return [
                'valid' => false,
                'error' => 'Code promo introuvable.',
            ];
        }

        if (!$promoCode->is_active) {
            return [
                'valid' => false,
                'error' => 'Ce code promo n\'est plus actif.',
            ];
        }

        if ($promoCode->valid_from && $promoCode->valid_from->isFuture()) {
            return [
                'valid' => false,
                'error' => 'Ce code promo n\'est pas encore valide.',
            ];
        }

        if ($promoCode->valid_until && $promoCode->valid_until->isPast()) {
            return [
                'valid' => false,
                'error' => 'Ce code promo a expiré.',
            ];
        }

        if ($promoCode->usage_limit && $promoCode->usage_count >= $promoCode->usage_limit) {
            return [
                'valid' => false,
                'error' => 'Ce code promo a atteint sa limite d\'utilisation.',
            ];
        }

        $user = User::find($userId);
        if (!$promoCode->canBeUsedBy($user)) {
            return [
                'valid' => false,
                'error' => 'Vous ne pouvez pas utiliser ce code promo.',
            ];
        }

        if (!$promoCode->isApplicableToResidence($residenceId)) {
            return [
                'valid' => false,
                'error' => 'Ce code n\'est pas valide pour cette résidence.',
            ];
        }

        if (!$promoCode->isApplicableToNights($nights)) {
            return [
                'valid' => false,
                'error' => 'Minimum '.$promoCode->min_nights.' nuits requis pour ce code.',
            ];
        }

        if (!$promoCode->isApplicableToAmount($subtotal)) {
            return [
                'valid' => false,
                'error' => 'Montant minimum '.number_format((float) $promoCode->min_amount, 0, ',', ' ').' FCFA requis.',
            ];
        }

        $discount = $promoCode->calculateDiscount($subtotal);

        return [
            'valid' => true,
            'code' => $promoCode->code,
            'name' => $promoCode->name,
            'type' => $promoCode->type,
            'value' => $promoCode->value,
            'discount' => $discount,
            'formatted_discount' => number_format($discount, 0, ',', ' ').' FCFA',
            'formatted_value' => $promoCode->getFormattedValue(),
        ];
    }

    /**
     * Créer un nouveau code promo
     */
    public function create(array $data): PromoCode
    {
        $data['code'] = strtoupper($data['code'] ?? Str::random(8));

        return PromoCode::create($data);
    }

    /**
     * Générer un code promo unique
     */
    public function generateUniqueCode(string $prefix = ''): string
    {
        do {
            $code = $prefix.strtoupper(Str::random(8 - strlen($prefix)));
        } while (PromoCode::where('code', $code)->exists());

        return $code;
    }

    /**
     * Créer un code de parrainage pour un utilisateur
     */
    public function createReferralCode(User $user): PromoCode
    {
        $code = 'REF'.strtoupper(substr(md5($user->id.time()), 0, 5));

        return $this->create([
            'code' => $code,
            'name' => 'Parrainage '.$user->first_name,
            'description' => 'Code de parrainage',
            'type' => 'percentage',
            'value' => 5,
            'max_discount' => config('rezi.promo.welcome_max_discount', 25000),
            'user_ids' => null, // Tous les utilisateurs peuvent l'utiliser
            'first_booking_only' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Créer un code de bienvenue pour un nouvel utilisateur
     */
    public function createWelcomeCode(User $user): PromoCode
    {
        $code = 'BIENVENUE'.$user->id;

        return $this->create([
            'code' => $code,
            'name' => 'Bienvenue '.$user->first_name,
            'description' => 'Réduction de bienvenue',
            'type' => 'percentage',
            'value' => 10,
            'max_discount' => config('rezi.promo.seasonal_max_discount', 50000),
            'user_ids' => [$user->id],
            'per_user_limit' => 1,
            'valid_until' => now()->addDays(30),
            'first_booking_only' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Créer une campagne de codes promo
     */
    public function createCampaign(array $campaignData, int $quantity): array
    {
        $codes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $data = array_merge($campaignData, [
                'code' => $this->generateUniqueCode($campaignData['prefix'] ?? ''),
            ]);
            unset($data['prefix']);

            $codes[] = $this->create($data);
        }

        return $codes;
    }

    /**
     * Obtenir les statistiques d'utilisation d'un code promo
     */
    public function getCodeStats(PromoCode $promoCode): array
    {
        $uses = PromoCodeUse::where('promo_code_id', $promoCode->id)->get();

        return [
            'total_uses' => $uses->count(),
            'total_discount_given' => $uses->sum('discount_amount'),
            'unique_users' => $uses->unique('user_id')->count(),
            'remaining_uses' => $promoCode->getRemainingUsages(),
            'is_active' => $promoCode->isValid(),
            'conversion_rate' => $promoCode->usage_limit
                ? round(($uses->count() / $promoCode->usage_limit) * 100, 1)
                : null,
        ];
    }

    /**
     * Rechercher des codes promo
     */
    public function search(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = PromoCode::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['valid'])) {
            if ($filters['valid']) {
                $query->active();
            }
        }

        if (isset($filters['code'])) {
            $query->where('code', 'LIKE', '%'.$filters['code'].'%');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Désactiver les codes expirés
     */
    public function deactivateExpiredCodes(): int
    {
        return PromoCode::where('is_active', true)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now())
            ->update(['is_active' => false]);
    }

    /**
     * Obtenir les meilleurs codes actifs
     */
    public function getActiveCodes(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return PromoCode::active()
            ->where('first_booking_only', false) // Exclure les codes personnels
            ->whereNull('user_ids') // Codes publics uniquement
            ->orderBy('value', 'desc')
            ->limit($limit)
            ->get();
    }
}
