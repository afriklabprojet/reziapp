<?php

namespace Database\Factories;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PushSubscription>
 */
class PushSubscriptionFactory extends Factory
{
    protected $model = PushSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.fake()->uuid(),
            'public_key' => base64_encode(random_bytes(65)),
            'auth_token' => base64_encode(random_bytes(16)),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Subscription FCM (Firebase Cloud Messaging)
     */
    public function fcm(): static
    {
        return $this->state(fn (array $attributes) => [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/'.fake()->uuid(),
        ]);
    }

    /**
     * Subscription Mozilla Push Service
     */
    public function mozilla(): static
    {
        return $this->state(fn (array $attributes) => [
            'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/'.fake()->uuid(),
        ]);
    }

    /**
     * Subscription Apple Push Notification
     */
    public function apple(): static
    {
        return $this->state(fn (array $attributes) => [
            'endpoint' => 'https://web.push.apple.com/'.fake()->uuid(),
        ]);
    }
}
