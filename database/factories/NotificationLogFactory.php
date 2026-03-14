<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => fake()->randomElement([
                NotificationLog::CHANNEL_EMAIL,
                NotificationLog::CHANNEL_PUSH,
                NotificationLog::CHANNEL_SMS,
            ]),
            'type' => fake()->randomElement([
                NotificationLog::TYPE_MESSAGE,
                NotificationLog::TYPE_PAYMENT,
                NotificationLog::TYPE_SYSTEM,
            ]),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'data' => [
                'url' => fake()->url(),
            ],
            'status' => NotificationLog::STATUS_PENDING,
            'sent_at' => null,
            'read_at' => null,
            'error_message' => null,
        ];
    }

    /**
     * Notification envoyée
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_SENT,
            'sent_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Notification lue
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_SENT,
            'sent_at' => fake()->dateTimeBetween('-1 day', '-1 hour'),
            'read_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Notification échouée
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationLog::STATUS_FAILED,
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Notification email
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationLog::CHANNEL_EMAIL,
        ]);
    }

    /**
     * Notification push
     */
    public function push(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationLog::CHANNEL_PUSH,
        ]);
    }

    /**
     * Notification SMS
     */
    public function sms(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationLog::CHANNEL_SMS,
        ]);
    }

    /**
     * Type message
     */
    public function messageType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationLog::TYPE_MESSAGE,
            'title' => 'Nouveau message',
        ]);
    }

    /**
     * Type paiement
     */
    public function paymentType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationLog::TYPE_PAYMENT,
            'title' => 'Paiement reçu',
        ]);
    }
}
