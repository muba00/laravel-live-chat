<?php

namespace muba00\LaravelLiveChat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use muba00\LaravelLiveChat\Models\Conversation;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\muba00\LaravelLiveChat\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $userModel = config('live-chat.user_model', 'App\\Models\\User');

        // Generate two different user IDs and ensure user1_id < user2_id
        $userId1 = fake()->numberBetween(1, 1000);
        $userId2 = fake()->numberBetween(1, 1000);

        while ($userId1 === $userId2) {
            $userId2 = fake()->numberBetween(1, 1000);
        }

        [$user1Id, $user2Id] = $userId1 < $userId2 ? [$userId1, $userId2] : [$userId2, $userId1];

        return [
            'user1_id' => $user1Id,
            'user2_id' => $user2Id,
            'last_message_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Define a conversation with specific users.
     */
    public function betweenUsers(int $user1Id, int $user2Id): static
    {
        [$smaller, $larger] = $user1Id < $user2Id ? [$user1Id, $user2Id] : [$user2Id, $user1Id];

        return $this->state(fn (array $attributes) => [
            'user1_id' => $smaller,
            'user2_id' => $larger,
        ]);
    }

    /**
     * Define a conversation with a recent message.
     */
    public function withRecentMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Define a conversation without any messages.
     */
    public function withoutMessages(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => null,
        ]);
    }
}
