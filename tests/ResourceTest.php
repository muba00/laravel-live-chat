<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

uses(RefreshDatabase::class);

describe('API Resources', function () {
    describe('MessageResource', function () {
        it('formats message correctly in API response', function () {
            $user1 = createUser('Test User', 'test@test.com');
            $user2 = createUser('Other User', 'other@test.com');
            
            $conversation = Conversation::factory()->create([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ]);

            $message = Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user1->id,
                'message' => 'Hello, world!',
                'read_at' => null,
            ]);

            $response = $this->actingAs($user1)
                ->getJson("/chat/api/conversations/{$conversation->id}/messages");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'conversation_id',
                            'sender_id',
                            'message',
                            'is_read',
                            'read_at',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ]);

            $data = $response->json('data.0');
            expect($data['id'])->toBe($message->id);
            expect($data['message'])->toBe('Hello, world!');
            expect($data['is_read'])->toBe(false);
            expect($data['read_at'])->toBeNull();
        });

        it('includes sender information when loaded', function () {
            $user1 = createUser('Test User', 'test@test.com');
            $user2 = createUser('Other User', 'other@test.com');
            
            $conversation = Conversation::factory()->create([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ]);

            $message = Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user1->id,
            ]);

            $response = $this->actingAs($user1)
                ->getJson("/chat/api/conversations/{$conversation->id}/messages");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'sender' => ['id', 'name', 'email'],
                        ],
                    ],
                ]);
        });

        it('shows is_read as true when read_at is set', function () {
            $user1 = createUser('Test User', 'test@test.com');
            $user2 = createUser('Other User', 'other@test.com');
            
            $conversation = Conversation::factory()->create([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ]);

            $message = Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user2->id,
                'read_at' => now(),
            ]);

            $response = $this->actingAs($user1)
                ->getJson("/chat/api/conversations/{$conversation->id}/messages");

            $response->assertOk();
            $data = $response->json('data.0');
            expect($data['is_read'])->toBe(true);
            expect($data['read_at'])->not->toBeNull();
        });
    });

    describe('ConversationResource', function () {
        it('formats conversation correctly in API response', function () {
            $user1 = createUser('User One', 'user1@test.com');
            $user2 = createUser('User Two', 'user2@test.com');

            $conversation = Conversation::factory()->create([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
                'last_message_at' => now(),
            ]);

            $response = $this->actingAs($user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'user1_id',
                            'user2_id',
                            'last_message_at',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ]);

            $data = $response->json('data.0');
            expect($data['id'])->toBe($conversation->id);
            expect($data['user1_id'])->toBe($user1->id);
            expect($data['user2_id'])->toBe($user2->id);
        });

        it('includes other_user information', function () {
            $user1 = createUser('User One', 'user1@test.com');
            $user2 = createUser('User Two', 'user2@test.com');

            $conversation = Conversation::factory()->create([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ]);

            $response = $this->actingAs($user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'other_user' => ['id', 'name', 'email'],
                        ],
                    ],
                ]);

            $data = $response->json('data.0');
            expect($data['other_user']['id'])->toBe($user2->id);
        });

        it('includes latest_message when available', function () {
            $user1 = createUser('User One', 'user1@test.com');
            $user2 = createUser('User Two', 'user2@test.com');

            $conversation = Conversation::factory()->create([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ]);

            $message = Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user1->id,
                'message' => 'Latest message',
            ]);

            $response = $this->actingAs($user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'latest_message' => ['id', 'message'],
                        ],
                    ],
                ]);
        });

        it('includes unread_count', function () {
            $user1 = createUser('User One', 'user1@test.com');
            $user2 = createUser('User Two', 'user2@test.com');

            $conversation = Conversation::factory()->create([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ]);

            Message::factory()->count(5)->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user2->id,
                'read_at' => null,
            ]);

            $response = $this->actingAs($user1)
                ->getJson('/chat/api/conversations');

            $response->assertOk()
                ->assertJsonPath('data.0.unread_count', 5);
        });
    });
});
