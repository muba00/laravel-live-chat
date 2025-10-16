<?php

use Illuminate\Support\Facades\Schema;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

beforeEach(function () {
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

test('message can be created', function () {
    $conversation = Conversation::factory()->create();
    $message = Message::factory()
        ->forConversation($conversation)
        ->fromSender(1)
        ->create();

    expect($message->conversation_id)->toBe($conversation->id)
        ->and($message->sender_id)->toBe(1);
});

test('message belongs to conversation', function () {
    $conversation = Conversation::factory()->create();
    $message = Message::factory()->forConversation($conversation)->create();

    expect($message->conversation)->toBeInstanceOf(Conversation::class)
        ->and($message->conversation->id)->toBe($conversation->id);
});

test('message can check if it is read', function () {
    $unreadMessage = Message::factory()->unread()->create();
    $readMessage = Message::factory()->read()->create();

    expect($unreadMessage->isRead())->toBeFalse()
        ->and($readMessage->isRead())->toBeTrue();
});

test('message can be marked as read', function () {
    $message = Message::factory()->unread()->create();

    expect($message->isRead())->toBeFalse();

    $message->markAsRead();

    expect($message->fresh()->isRead())->toBeTrue();
});

test('message mark as read does not update if already read', function () {
    $message = Message::factory()->read()->create();
    $originalReadAt = $message->read_at;

    sleep(1);
    $message->markAsRead();

    expect($message->fresh()->read_at->timestamp)->toBe($originalReadAt->timestamp);
});

test('message sender always sees their own message as read', function () {
    $message = Message::factory()->unread()->fromSender(1)->create();
    $sender = (object) ['id' => 1];

    expect($message->isReadBy($sender))->toBeTrue();
});

test('message recipient sees unread message as unread', function () {
    $message = Message::factory()->unread()->fromSender(1)->create();
    $recipient = (object) ['id' => 2];

    expect($message->isReadBy($recipient))->toBeFalse();
});

test('message recipient sees read message as read', function () {
    $message = Message::factory()->read()->fromSender(1)->create();
    $recipient = (object) ['id' => 2];

    expect($message->isReadBy($recipient))->toBeTrue();
});

test('messages can be filtered by unread status', function () {
    $conversation = Conversation::factory()->create();

    Message::factory()->count(3)->unread()->forConversation($conversation)->create();
    Message::factory()->count(2)->read()->forConversation($conversation)->create();

    $unreadMessages = Message::unread()->get();

    expect($unreadMessages)->toHaveCount(3);
});

test('messages can be filtered by read status', function () {
    $conversation = Conversation::factory()->create();

    Message::factory()->count(3)->unread()->forConversation($conversation)->create();
    Message::factory()->count(2)->read()->forConversation($conversation)->create();

    $readMessages = Message::read()->get();

    expect($readMessages)->toHaveCount(2);
});

test('messages can be filtered by sender', function () {
    $conversation = Conversation::factory()->create();

    Message::factory()->count(3)->fromSender(1)->forConversation($conversation)->create();
    Message::factory()->count(2)->fromSender(2)->forConversation($conversation)->create();

    $sender = (object) ['id' => 1];
    $messages = Message::bySender($sender)->get();

    expect($messages)->toHaveCount(3);
});

test('messages can be filtered by not sender', function () {
    $conversation = Conversation::factory()->create();

    Message::factory()->count(3)->fromSender(1)->forConversation($conversation)->create();
    Message::factory()->count(2)->fromSender(2)->forConversation($conversation)->create();

    $sender = (object) ['id' => 1];
    $messages = Message::notBySender($sender)->get();

    expect($messages)->toHaveCount(2);
});

test('message with custom content', function () {
    $customMessage = 'This is a custom message';
    $message = Message::factory()
        ->withMessage($customMessage)
        ->create();

    expect($message->message)->toBe($customMessage);
});

test('recent messages are created with recent timestamp', function () {
    $message = Message::factory()->recent()->create();

    $oneHourAgo = now()->subHour();

    expect($message->created_at->isAfter($oneHourAgo))->toBeTrue();
});
