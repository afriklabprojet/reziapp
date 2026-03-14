<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(20000, 200000);
        $fee = round($amount * 0.02);

        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'booking_id' => Booking::factory(),
            'reference' => 'PAY-'.strtoupper(fake()->unique()->bothify('????####')),
            'amount' => $amount,
            'fee' => $fee,
            'total_amount' => $amount + $fee,
            'currency' => 'XOF',
            'type' => Payment::TYPE_BOOKING,
            'status' => Payment::STATUS_PENDING,
            'phone_number' => fake()->optional()->phoneNumber(),
            'provider_reference' => null,
            'provider_transaction_id' => null,
            'provider_response' => null,
            'initiated_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'expires_at' => now()->addMinutes(15),
            'metadata' => [],
        ];
    }

    /**
     * Paiement en attente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    /**
     * Paiement en cours
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PROCESSING,
            'initiated_at' => now(),
            'provider_reference' => 'JEKO-'.strtoupper(fake()->bothify('??????')),
        ]);
    }

    /**
     * Paiement complété
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_COMPLETED,
            'initiated_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'provider_reference' => 'JEKO-'.strtoupper(fake()->bothify('??????')),
            'provider_transaction_id' => 'TXN-'.strtoupper(fake()->bothify('########')),
        ]);
    }

    /**
     * Paiement échoué
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
            'initiated_at' => now()->subMinutes(5),
            'failed_at' => now(),
            'failure_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Paiement remboursé
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_REFUNDED,
            'completed_at' => now()->subDay(),
        ]);
    }

    /**
     * Orange Money
     */
    public function orangeMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_number' => '07'.fake()->numerify('########'),
            'metadata' => array_merge($attributes['metadata'] ?? [], ['operator' => 'orange_money']),
        ]);
    }

    /**
     * MTN Mobile Money
     */
    public function mtnMomo(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_number' => '05'.fake()->numerify('########'),
            'metadata' => array_merge($attributes['metadata'] ?? [], ['operator' => 'mtn_momo']),
        ]);
    }
}
