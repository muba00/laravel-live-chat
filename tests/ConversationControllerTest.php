<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user1 = createUser('User One', 'user1@test.com');
    $this->user2 = createUser('User Two', 'user2@test.com');
    $this->user3 = createUser('User Three', 'user3@test.com');
});

describe('ConversationController', function () {
    describe('index', function () {
        it('returns all conversations for authenticated user', function () {
            $conversation1 = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user2->id,
            ]);
            $conversation2 = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user3->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'user1_id', 'user2_id', 'created_at', 'updated_at'],
                    ],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ])
                ->assertJsonCount(2, 'data');
        });

        it('returns conversations ordered by last_message_at', function () {
            $conversation1 = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user2->id,
                'last_message_at' => now()->subHours(2),
            ]);
            $conversation2 = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user3->id,
                'last_message_at' => now()->subHour(),
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk();
            $data = $response->json('data');
            expect($data[0]['id'])->toBe($conversation2->id);
            expect($data[1]['id'])->toBe($conversation1->id);
        });

        it('includes unread message count for each conversation', function () {
            $conversation = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user2->id,
            ]);

            Message::factory()->count(3)->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $this->user2->id,
                'read_at' => null,
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk()
                ->assertJsonPath('data.0.unread_count', 3);
        });

        it('includes latest message for each conversation', function () {
            $conversation = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user2->id,
            ]);

            Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $this->user1->id,
                'message' => 'Old message',
            ]);

            $latestMessage = Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $this->user2->id,
                'message' => 'Latest message',
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk()
                ->assertJsonPath('data.0.latest_message.message', 'Latest message');
        });

        it('paginates conversations correctly', function () {
            // Create 25 different users to have 25 unique conversations
            for ($i = 100; $i < 125; $i++) {
                $otherUser = createUser("User {$i}", "user{$i}@test.com");
                Conversation::factory()->create([
                    'user1_id' => $this->user1->id,
                    'user2_id' => $otherUser->id,
                ]);
            }

            $response = $this->actingAs($this->user1)
                ->getJson('/chat/api/conversations?per_page=10');

            $response->assertOk()
                ->assertJsonPath('meta.per_page', 10)
                ->assertJsonPath('meta.total', 25)
                ->assertJsonCount(10, 'data');
        });

        it('requires authentication', function () {
            $response = $this->getJson('/chat/api/conversations');

            $response->assertUnauthorized();
        });
    });

    describe('show', function () {
        it('returns a specific conversation', function () {
            $conversation = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user2->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson("/chat/api/conversations/{$conversation->id}");

            $response->assertOk()
                ->assertJsonPath('data.id', $conversation->id)
                ->assertJsonStructure([
                    'data' => ['id', 'user1_id', 'user2_id', 'created_at', 'updated_at'],
                ]);
        });

        it('returns 403 if user is not part of the conversation', function () {
            $conversation = Conversation::factory()->create([
                'user1_id' => $this->user2->id,
                'user2_id' => $this->user3->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson("/chat/api/conversations/{$conversation->id}");

            $response->assertForbidden();
        });

        it('returns 404 if conversation does not exist', function () {
            $response = $this->actingAs($this->user1)
                ->getJson('/chat/api/conversations/999');

            $response->assertNotFound();
        });
    });

    describe('store', function () {
        it('creates a new conversation between two users', function () {
            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations', [
                    'user_id' => $this->user2->id,
                ]);

            $response->assertCreated()
                ->assertJsonStructure([
                    'data' => ['id', 'user1_id', 'user2_id'],
                    'message',
                ]);

            $this->assertDatabaseHas('live_chat_conversations', [
                'user1_id' => min($this->user1->id, $this->user2->id),
                'user2_id' => max($this->user1->id, $this->user2->id),
            ]);
        });

        it('returns existing conversation if one already exists', function () {
            $existingConversation = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user2->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations', [
                    'user_id' => $this->user2->id,
                ]);

            $response->assertOk()
                ->assertJsonPath('data.id', $existingConversation->id)
                ->assertJsonPath('message', 'Conversation already exists.');

            // Should not create a new conversation
            expect(Conversation::count())->toBe(1);
        });

        it('finds existing conversation regardless of user order', function () {
            $existingConversation = Conversation::factory()->create([
                'user1_id' => $this->user2->id,
                'user2_id' => $this->user1->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations', [
                    'user_id' => $this->user2->id,
                ]);

            $response->assertOk()
                ->assertJsonPath('data.id', $existingConversation->id);
        });

        it('prevents user from creating conversation with themselves', function () {
            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations', [
                    'user_id' => $this->user1->id,
                ]);

            $response->assertStatus(422)
                ->assertJsonPath('message', 'Cannot start a conversation with yourself.');
        });

        it('validates user_id is required', function () {
            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);
        });

        it('validates user_id exists in users table', function () {
            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations', [
                    'user_id' => 9999,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['user_id']);
        });
    });

    describe('destroy', function () {
        it('deletes a conversation and its messages', function () {
            $conversation = Conversation::factory()->create([
                'user1_id' => $this->user1->id,
                'user2_id' => $this->user2->id,
            ]);

            Message::factory()->count(5)->create([
                'conversation_id' => $conversation->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->deleteJson("/chat/api/conversations/{$conversation->id}");

            $response->assertOk()
                ->assertJsonPath('message', 'Conversation deleted successfully.');

            $this->assertDatabaseMissing('live_chat_conversations', [
                'id' => $conversation->id,
            ]);

            expect(Message::where('conversation_id', $conversation->id)->count())->toBe(0);
        });

        it('returns 403 if user is not part of the conversation', function () {
            $conversation = Conversation::factory()->create([
                'user1_id' => $this->user2->id,
                'user2_id' => $this->user3->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->deleteJson("/chat/api/conversations/{$conversation->id}");

            $response->assertForbidden();
        });
    });
});
