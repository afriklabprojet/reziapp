<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'content' => fake()->paragraph(),
            'read_at' => null,
        ];
    }

    /**
     * Message lu
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Message non lu
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Message avec pièce jointe image
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'attachments' => [
                [
                    'type' => 'image',
                    'url' => '/storage/messages/attachments/'.fake()->uuid().'.jpg',
                    'name' => fake()->word().'.jpg',
                    'size' => fake()->numberBetween(50000, 500000),
                ],
            ],
        ]);
    }

    /**
     * Message en réponse à un autre
     */
    public function replyTo(Message $message): static
    {
        return $this->state(fn (array $attributes) => [
            'conversation_id' => $message->conversation_id,
            'metadata' => array_merge($attributes['metadata'] ?? [], ['reply_to_id' => $message->id]),
        ]);
    }
}
