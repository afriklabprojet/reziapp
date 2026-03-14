<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'owner_id' => User::factory(),
            'residence_id' => Residence::factory(),
            'last_message_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Conversation archivée par l'utilisateur
     */
    public function archivedByUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Conversation archivée par le propriétaire
     */
    public function archivedByOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Sans résidence associée
     */
    public function withoutResidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'residence_id' => null,
        ]);
    }
}
