<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use muba00\LaravelLiveChat\Events\MessageSent;
use muba00\LaravelLiveChat\Events\UserTyping;
use muba00\LaravelLiveChat\Facades\LaravelLiveChat;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Enable foreign key constraints for SQLite
    if (DB::connection()->getDriverName() === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = ON');
    }

    // Create necessary tables
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });

    Schema::create('live_chat_conversations', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user1_id');
        $table->unsignedBigInteger('user2_id');
        $table->timestamp('last_message_at')->nullable();
        $table->timestamps();
        $table->index(['user1_id', 'user2_id']);
        $table->index('last_message_at');
        $table->unique(['user1_id', 'user2_id']);
    });

    Schema::create('live_chat_messages', function ($table) {
        $table->id();
        $table->foreignId('conversation_id')
            ->constrained('live_chat_conversations')
            ->onDelete('cascade');
        $table->unsignedBigInteger('sender_id');
        $table->text('message');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
        $table->index(['conversation_id', 'created_at']);
        $table->index('sender_id');
        $table->index('read_at');
    });

    // Create test users
    $this->user1 = createUser('User 1', 'user1@example.com');
    $this->user2 = createUser('User 2', 'user2@example.com');
    $this->user3 = createUser('User 3', 'user3@example.com');
});

afterEach(function () {
    Schema::dropIfExists('live_chat_messages');
    Schema::dropIfExists('live_chat_conversations');
    Schema::dropIfExists('users');
});

describe('MessageSent Event', function () {
    it('dispatches MessageSent event when a message is sent', function () {
        Event::fake([MessageSent::class]);

        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $message = LaravelLiveChat::sendMessage($conversation, $this->user1, 'Hello World!');

        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id
                && $event->message->message === 'Hello World!';
        });
    });

    it('does not dispatch MessageSent event when broadcasting is disabled', function () {
        config(['live-chat.broadcasting.enabled' => false]);
        Event::fake([MessageSent::class]);

        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Hello World!');

        Event::assertNotDispatched(MessageSent::class);
    });

    it('broadcasts on the correct channel', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'message' => 'Test message',
        ]);

        $event = new MessageSent($message);
        $channels = $event->broadcastOn();

        expect($channels)->toHaveCount(1);
        expect($channels[0]->name)->toBe("private-chat.{$conversation->id}");
    });

    it('broadcasts with correct data structure', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'message' => 'Test message',
        ]);

        $event = new MessageSent($message);
        $data = $event->broadcastWith();

        expect($data)->toHaveKeys(['id', 'conversation_id', 'sender_id', 'message', 'read_at', 'created_at']);
        expect($data['id'])->toBe($message->id);
        expect($data['conversation_id'])->toBe($conversation->id);
        expect($data['sender_id'])->toBe($this->user1->id);
        expect($data['message'])->toBe('Test message');
        expect($data['read_at'])->toBeNull();
        expect($data['created_at'])->not->toBeNull();
    });

    it('uses correct broadcast event name', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'message' => 'Test',
        ]);

        $event = new MessageSent($message);

        expect($event->broadcastAs())->toBe('message.sent');
    });

    it('uses custom channel prefix from config', function () {
        config(['live-chat.broadcasting.channel_prefix' => 'custom']);

        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user1->id,
            'message' => 'Test',
        ]);

        $event = new MessageSent($message);
        $channels = $event->broadcastOn();

        expect($channels[0]->name)->toBe("private-custom.{$conversation->id}");
    });
});

describe('UserTyping Event', function () {
    it('broadcasts typing indicator via broadcastTyping method', function () {
        Event::fake([UserTyping::class]);

        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::broadcastTyping($conversation, $this->user1, true);

        Event::assertDispatched(UserTyping::class, function ($event) use ($conversation) {
            return $event->conversationId === $conversation->id
                && $event->userId === $this->user1->id
                && $event->isTyping === true;
        });
    });

    it('broadcasts stopped typing indicator', function () {
        Event::fake([UserTyping::class]);

        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::broadcastTyping($conversation, $this->user1, false);

        Event::assertDispatched(UserTyping::class, function ($event) {
            return $event->userId === $this->user1->id
                && $event->isTyping === false;
        });
    });

    it('does not broadcast typing when broadcasting is disabled', function () {
        config(['live-chat.broadcasting.enabled' => false]);
        Event::fake([UserTyping::class]);

        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::broadcastTyping($conversation, $this->user1, true);

        Event::assertNotDispatched(UserTyping::class);
    });

    it('does not broadcast typing when feature is disabled', function () {
        config(['live-chat.features.typing_indicators' => false]);
        Event::fake([UserTyping::class]);

        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::broadcastTyping($conversation, $this->user1, true);

        Event::assertNotDispatched(UserTyping::class);
    });

    it('broadcasts on correct channel', function () {
        $conversationId = 123;
        $userId = 456;

        $event = new UserTyping($conversationId, $userId, true);
        $channels = $event->broadcastOn();

        expect($channels)->toHaveCount(1);
        expect($channels[0]->name)->toBe('private-chat.123');
    });

    it('broadcasts with correct data structure', function () {
        $event = new UserTyping(123, 456, true);
        $data = $event->broadcastWith();

        expect($data)->toHaveKeys(['user_id', 'is_typing']);
        expect($data['user_id'])->toBe(456);
        expect($data['is_typing'])->toBe(true);
    });

    it('uses correct broadcast event name', function () {
        $event = new UserTyping(123, 456, true);

        expect($event->broadcastAs())->toBe('user.typing');
    });

    it('uses custom channel prefix from config', function () {
        config(['live-chat.broadcasting.channel_prefix' => 'custom']);

        $event = new UserTyping(123, 456, true);
        $channels = $event->broadcastOn();

        expect($channels[0]->name)->toBe('private-custom.123');
    });

    it('accepts conversation instance for broadcastTyping', function () {
        Event::fake([UserTyping::class]);

        $conversation = Conversation::create([
            'user1_id' => min($this->user1->id, $this->user2->id),
            'user2_id' => max($this->user1->id, $this->user2->id),
        ]);

        LaravelLiveChat::broadcastTyping($conversation, $this->user1, true);

        Event::assertDispatched(UserTyping::class);
    });
});

describe('Channel Authorization', function () {
    it('authorizes users who are part of the conversation', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        // Verify user1 is authorized
        $result = $conversation->includesUser($this->user1);
        expect($result)->toBeTrue();

        // Verify user2 is authorized
        $result = $conversation->includesUser($this->user2);
        expect($result)->toBeTrue();
    });

    it('denies authorization for users not part of the conversation', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        // User 3 should not be authorized
        $result = $conversation->includesUser($this->user3);
        expect($result)->toBeFalse();
    });

    it('returns false for non-existent conversations', function () {
        $conversation = Conversation::find(999999);

        expect($conversation)->toBeNull();
    });
});
