<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

beforeEach(function () {
    // Enable foreign key constraints for SQLite
    if (DB::connection()->getDriverName() === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = ON');
    }

    // Run migrations
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

    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('live_chat_messages');
    Schema::dropIfExists('live_chat_conversations');
    Schema::dropIfExists('users');
});

test('conversation can be created with two users', function () {
    $conversation = Conversation::factory()->betweenUsers(1, 2)->create();

    expect($conversation->user1_id)->toBe(1)
        ->and($conversation->user2_id)->toBe(2);
});

test('conversation ensures user1_id is always smaller than user2_id', function () {
    $conversation = Conversation::factory()->betweenUsers(5, 3)->create();

    expect($conversation->user1_id)->toBe(3)
        ->and($conversation->user2_id)->toBe(5);
});

test('conversation has messages relationship', function () {
    $conversation = Conversation::factory()->create();

    Message::factory()->count(3)->forConversation($conversation)->create();

    expect($conversation->messages)->toHaveCount(3);
});

test('conversation can get latest message', function () {
    $conversation = Conversation::factory()->create();

    Message::factory()->forConversation($conversation)->create(['created_at' => now()->subHour()]);
    $latestMessage = Message::factory()->forConversation($conversation)->create(['created_at' => now()]);

    $result = $conversation->latestMessage()->first();

    expect($result->id)->toBe($latestMessage->id);
});

test('conversation can get other user', function () {
    $conversation = Conversation::factory()->betweenUsers(1, 2)->create();

    $user1 = (object) ['id' => 1];
    $user2 = (object) ['id' => 2];

    // Mock the relationships
    $conversation->setRelation('user1', $user1);
    $conversation->setRelation('user2', $user2);

    $otherUser = $conversation->getOtherUser($user1);

    expect($otherUser->id)->toBe(2);
});

test('conversation returns null when user is not part of conversation', function () {
    $conversation = Conversation::factory()->betweenUsers(1, 2)->create();

    $user3 = (object) ['id' => 3];

    $otherUser = $conversation->getOtherUser($user3);

    expect($otherUser)->toBeNull();
});

test('conversation can check if it includes a user', function () {
    $conversation = Conversation::factory()->betweenUsers(1, 2)->create();

    $user1 = (object) ['id' => 1];
    $user2 = (object) ['id' => 2];
    $user3 = (object) ['id' => 3];

    expect($conversation->includesUser($user1))->toBeTrue()
        ->and($conversation->includesUser($user2))->toBeTrue()
        ->and($conversation->includesUser($user3))->toBeFalse();
});

test('conversation can be scoped for a specific user', function () {
    Conversation::factory()->betweenUsers(1, 2)->create();
    Conversation::factory()->betweenUsers(1, 3)->create();
    Conversation::factory()->betweenUsers(2, 3)->create();

    $user1 = (object) ['id' => 1];

    $conversations = Conversation::forUser($user1)->get();

    expect($conversations)->toHaveCount(2);
});

test('conversation can be found between two users', function () {
    $conversation = Conversation::factory()->betweenUsers(1, 2)->create();
    Conversation::factory()->betweenUsers(1, 3)->create();

    $user1 = (object) ['id' => 1];
    $user2 = (object) ['id' => 2];

    $found = Conversation::betweenUsers($user1, $user2)->first();

    expect($found->id)->toBe($conversation->id);
});

test('conversation can be found between two users regardless of order', function () {
    $conversation = Conversation::factory()->betweenUsers(1, 2)->create();

    $user1 = (object) ['id' => 1];
    $user2 = (object) ['id' => 2];

    $found1 = Conversation::betweenUsers($user1, $user2)->first();
    $found2 = Conversation::betweenUsers($user2, $user1)->first();

    expect($found1->id)->toBe($conversation->id)
        ->and($found2->id)->toBe($conversation->id);
});

test('conversation can update last message time', function () {
    $conversation = Conversation::factory()->withoutMessages()->create();

    expect($conversation->last_message_at)->toBeNull();

    $conversation->updateLastMessageTime();
    $conversation->refresh();

    expect($conversation->last_message_at)->not->toBeNull();
});

test('conversation deletes messages when deleted', function () {
    $conversation = Conversation::factory()->create();
    Message::factory()->count(3)->forConversation($conversation)->create();

    expect(Message::count())->toBe(3);

    $conversation->delete();

    expect(Message::count())->toBe(0);
});
