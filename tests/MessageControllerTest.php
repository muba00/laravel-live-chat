<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use muba00\LaravelLiveChat\Events\MessageSent;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user1 = createUser('User One', 'user1@test.com');
    $this->user2 = createUser('User Two', 'user2@test.com');
    $this->user3 = createUser('User Three', 'user3@test.com');

    $this->conversation = Conversation::factory()->create([
        'user1_id' => $this->user1->id,
        'user2_id' => $this->user2->id,
    ]);
});

describe('MessageController', function () {
    describe('index', function () {
        it('returns all messages in a conversation', function () {
            Message::factory()->count(5)->create([
                'conversation_id' => $this->conversation->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson("/chat/api/conversations/{$this->conversation->id}/messages");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'conversation_id', 'sender_id', 'message', 'is_read', 'created_at'],
                    ],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ])
                ->assertJsonCount(5, 'data');
        });

        it('returns messages ordered by created_at descending', function () {
            $oldMessage = Message::factory()->create([
                'conversation_id' => $this->conversation->id,
                'message' => 'Old message',
                'created_at' => now()->subHour(),
            ]);

            $newMessage = Message::factory()->create([
                'conversation_id' => $this->conversation->id,
                'message' => 'New message',
                'created_at' => now(),
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson("/chat/api/conversations/{$this->conversation->id}/messages");

            $response->assertOk();
            $data = $response->json('data');
            expect($data[0]['message'])->toBe('New message');
            expect($data[1]['message'])->toBe('Old message');
        });

        it('paginates messages correctly', function () {
            Message::factory()->count(60)->create([
                'conversation_id' => $this->conversation->id,
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson("/chat/api/conversations/{$this->conversation->id}/messages?per_page=20");

            $response->assertOk()
                ->assertJsonPath('meta.per_page', 20)
                ->assertJsonPath('meta.total', 60)
                ->assertJsonCount(20, 'data');
        });

        it('returns 403 if user is not part of the conversation', function () {
            $response = $this->actingAs($this->user3)
                ->getJson("/chat/api/conversations/{$this->conversation->id}/messages");

            $response->assertForbidden();
        });

        it('requires authentication', function () {
            $response = $this->getJson("/chat/api/conversations/{$this->conversation->id}/messages");

            $response->assertUnauthorized();
        });
    });

    describe('store', function () {
        it('creates a new message in a conversation', function () {
            Event::fake([MessageSent::class]);

            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages", [
                    'message' => 'Hello, world!',
                ]);

            $response->assertCreated()
                ->assertJsonPath('data.message', 'Hello, world!')
                ->assertJsonPath('data.sender_id', $this->user1->id)
                ->assertJsonStructure([
                    'data' => ['id', 'conversation_id', 'sender_id', 'message'],
                    'message',
                ]);

            $this->assertDatabaseHas('live_chat_messages', [
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user1->id,
                'message' => 'Hello, world!',
            ]);

            Event::assertDispatched(MessageSent::class);
        });

        it('updates conversation last_message_at timestamp', function () {
            $oldTimestamp = $this->conversation->last_message_at;

            $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages", [
                    'message' => 'Test message',
                ]);

            $this->conversation->refresh();
            expect($this->conversation->last_message_at)->not->toBe($oldTimestamp);
        });

        it('broadcasts message to other users', function () {
            Event::fake([MessageSent::class]);

            $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages", [
                    'message' => 'Broadcast test',
                ]);

            Event::assertDispatched(MessageSent::class);
        });

        it('validates message is required', function () {
            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
        });

        it('validates message is a string', function () {
            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages", [
                    'message' => ['invalid'],
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
        });

        it('validates message max length', function () {
            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages", [
                    'message' => str_repeat('a', 5001),
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['message']);
        });

        it('returns 403 if user is not part of the conversation', function () {
            $response = $this->actingAs($this->user3)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages", [
                    'message' => 'Unauthorized message',
                ]);

            $response->assertForbidden();
        });

        it('returns 404 if conversation does not exist', function () {
            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations/999/messages', [
                    'message' => 'Test message',
                ]);

            $response->assertNotFound();
        });
    });

    describe('markAsRead', function () {
        it('marks all unread messages in conversation as read', function () {
            $messages = Message::factory()->count(3)->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user2->id,
                'read_at' => null,
            ]);

            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages/mark-read");

            $response->assertOk()
                ->assertJsonPath('count', 3);

            foreach ($messages as $message) {
                $message->refresh();
                expect($message->isRead())->toBeTrue();
            }
        });

        it('marks specific messages as read', function () {
            $message1 = Message::factory()->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user2->id,
                'read_at' => null,
            ]);

            $message2 = Message::factory()->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user2->id,
                'read_at' => null,
            ]);

            $message3 = Message::factory()->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user2->id,
                'read_at' => null,
            ]);

            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages/mark-read", [
                    'message_ids' => [$message1->id, $message2->id],
                ]);

            $response->assertOk()
                ->assertJsonPath('count', 2);

            $message1->refresh();
            $message2->refresh();
            $message3->refresh();

            expect($message1->isRead())->toBeTrue();
            expect($message2->isRead())->toBeTrue();
            expect($message3->isRead())->toBeFalse();
        });

        it('does not mark own messages as read', function () {
            Message::factory()->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user1->id,
                'read_at' => null,
            ]);

            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages/mark-read");

            $response->assertOk()
                ->assertJsonPath('count', 0);
        });

        it('returns 403 if user is not part of the conversation', function () {
            $response = $this->actingAs($this->user3)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages/mark-read");

            $response->assertForbidden();
        });

        it('validates message_ids is an array', function () {
            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/messages/mark-read", [
                    'message_ids' => 'invalid',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['message_ids']);
        });
    });

    describe('unreadCount', function () {
        it('returns unread message count for a conversation', function () {
            Message::factory()->count(5)->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user2->id,
                'read_at' => null,
            ]);

            Message::factory()->count(2)->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user2->id,
                'read_at' => now(),
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson("/chat/api/conversations/{$this->conversation->id}/messages/unread-count");

            $response->assertOk()
                ->assertJsonPath('unread_count', 5);
        });

        it('does not count own messages as unread', function () {
            Message::factory()->count(3)->create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->user1->id,
                'read_at' => null,
            ]);

            $response = $this->actingAs($this->user1)
                ->getJson("/chat/api/conversations/{$this->conversation->id}/messages/unread-count");

            $response->assertOk()
                ->assertJsonPath('unread_count', 0);
        });

        it('returns 403 if user is not part of the conversation', function () {
            $response = $this->actingAs($this->user3)
                ->getJson("/chat/api/conversations/{$this->conversation->id}/messages/unread-count");

            $response->assertForbidden();
        });
    });
});
