<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use muba00\LaravelLiveChat\Events\UserTyping;
use muba00\LaravelLiveChat\Models\Conversation;

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

describe('TypingController', function () {
    describe('store', function () {
        it('broadcasts typing indicator to other users', function () {
            Event::fake([UserTyping::class]);

            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/typing", [
                    'is_typing' => true,
                ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Typing indicator sent successfully.');

            Event::assertDispatched(UserTyping::class);
        });

        it('broadcasts when user stops typing', function () {
            Event::fake([UserTyping::class]);

            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/typing", [
                    'is_typing' => false,
                ]);

            $response->assertOk();

            Event::assertDispatched(UserTyping::class);
        });

        it('validates is_typing is required', function () {
            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/typing", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['is_typing']);
        });

        it('validates is_typing is boolean', function () {
            $response = $this->actingAs($this->user1)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/typing", [
                    'is_typing' => 'invalid',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['is_typing']);
        });

        it('returns 403 if user is not part of the conversation', function () {
            $response = $this->actingAs($this->user3)
                ->postJson("/chat/api/conversations/{$this->conversation->id}/typing", [
                    'is_typing' => true,
                ]);

            $response->assertForbidden();
        });

        it('returns 404 if conversation does not exist', function () {
            $response = $this->actingAs($this->user1)
                ->postJson('/chat/api/conversations/999/typing', [
                    'is_typing' => true,
                ]);

            $response->assertNotFound();
        });

        it('requires authentication', function () {
            $response = $this->postJson("/chat/api/conversations/{$this->conversation->id}/typing", [
                'is_typing' => true,
            ]);

            $response->assertUnauthorized();
        });
    });
});
