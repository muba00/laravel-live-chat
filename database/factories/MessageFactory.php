<?php

namespace muba00\LaravelLiveChat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\muba00\LaravelLiveChat\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => fake()->numberBetween(1, 1000),
            'message' => fake()->sentence(10),
            'read_at' => fake()->optional(0.7)->dateTimeBetween('-1 day', 'now'),
        ];
    }

    /**
     * Define a message for a specific conversation.
     */
    public function forConversation(Conversation|int $conversation): static
    {
        $conversationId = $conversation instanceof Conversation
            ? $conversation->id
            : $conversation;

        return $this->state(fn (array $attributes) => [
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Define a message from a specific sender.
     */
    public function fromSender(int $senderId): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_id' => $senderId,
        ]);
    }

    /**
     * Define an unread message.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Define a read message.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Define a message with specific content.
     */
    public function withMessage(string $message): static
    {
        return $this->state(fn (array $attributes) => [
            'message' => $message,
        ]);
    }

    /**
     * Define a recent message.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
