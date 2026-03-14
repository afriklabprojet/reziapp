<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('now', '+1 month');
        $checkOut = fake()->dateTimeBetween($checkIn, '+2 months');
        $nights = max(1, (int) $checkIn->diff($checkOut)->days);
        $pricePerNight = fake()->numberBetween(15000, 100000);
        $subtotal = $pricePerNight * $nights;
        $cleaningFee = fake()->numberBetween(5000, 15000);
        $serviceFee = fake()->numberBetween(2000, 10000);

        return [
            'user_id' => User::factory(),
            'residence_id' => Residence::factory(),
            'cancellation_policy_id' => fn () => CancellationPolicy::first()?->id ?? CancellationPolicy::create([
                'name' => 'flexible',
                'display_name' => 'Flexible',
                'description' => 'Politique flexible par défaut',
                'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
                'is_active' => true,
            ])->id,
            'reference' => 'BK-'.strtoupper(fake()->unique()->bothify('????####')),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => fake()->numberBetween(1, 6),
            'nights' => $nights,
            'price_per_night' => $pricePerNight,
            'subtotal' => $subtotal,
            'cleaning_fee' => $cleaningFee,
            'service_fee' => $serviceFee,
            'total_amount' => $subtotal + $cleaningFee + $serviceFee,
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'guest_message' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Booking en attente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Booking confirmé
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Booking annulé
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Booking complété
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
