<?php

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-3 days', '+2 days');
        $endsAt   = $this->faker->dateTimeBetween('+3 days', '+30 days');

        return [
            'residence_id'    => Residence::factory(),
            'user_id'         => User::factory()->state(['role' => 'owner']),
            'title'           => $this->faker->randomElement([
                'Promo Week-end',
                'Offre Ramadan',
                'Réduction Noël',
                'Tarif spécial',
                'Soldes propriétaire',
            ]),
            'description'     => $this->faker->optional()->sentence(),
            'discount_type'   => 'percentage',
            'discount_value'  => $this->faker->randomElement([10, 15, 20, 25, 30]),
            'free_nights_min' => null,
            'min_nights'      => 1,
            'max_uses'        => null,
            'uses_count'      => 0,
            'starts_at'       => $startsAt,
            'ends_at'         => $endsAt,
            'booking_start'   => null,
            'booking_end'     => null,
            'is_active'       => true,
            'is_featured'     => false,
        ];
    }

    /** Promotion active maintenant. */
    public function active(): static
    {
        return $this->state([
            'starts_at' => now()->subDay(),
            'ends_at'   => now()->addDays(14),
            'is_active' => true,
        ]);
    }

    /** Promotion expirée. */
    public function expired(): static
    {
        return $this->state([
            'starts_at' => now()->subDays(30),
            'ends_at'   => now()->subDay(),
            'is_active' => true,
        ]);
    }

    /** Promotion désactivée (pas encore expirée). */
    public function inactive(): static
    {
        return $this->state([
            'starts_at' => now()->subDay(),
            'ends_at'   => now()->addDays(14),
            'is_active' => false,
        ]);
    }

    /** Promotion à venir. */
    public function upcoming(): static
    {
        return $this->state([
            'starts_at' => now()->addDays(3),
            'ends_at'   => now()->addDays(17),
            'is_active' => true,
        ]);
    }

    /** Expire dans < 7 jours. */
    public function expiringSoon(): static
    {
        return $this->state([
            'starts_at' => now()->subDay(),
            'ends_at'   => now()->addDays(3),
            'is_active' => true,
        ]);
    }

    /** Remise fixe en FCFA. */
    public function fixed(): static
    {
        return $this->state([
            'discount_type'  => 'fixed',
            'discount_value' => $this->faker->randomElement([5000, 10000, 15000]),
        ]);
    }

    /** Nuits offertes. */
    public function freeNights(): static
    {
        return $this->state([
            'discount_type'   => 'free_nights',
            'discount_value'  => 1,
            'free_nights_min' => 3,
        ]);
    }
}
