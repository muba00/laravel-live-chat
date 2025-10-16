<?php

use muba00\LaravelLiveChat\Facades\LaravelLiveChat;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

// Schema is now automatically managed by RefreshDatabase trait in TestCase
// Migrations are loaded from database/migrations/*.stub files

beforeEach(function () {
    // Create test users
    $this->user1 = createUser('User 1', 'user1@example.com');
    $this->user2 = createUser('User 2', 'user2@example.com');
    $this->user3 = createUser('User 3', 'user3@example.com');
});

describe('Conversation Creation', function () {
    test('can create a new conversation between two users', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->and($conversation->user1_id)->toBe(1)
            ->and($conversation->user2_id)->toBe(2);
    });

    test('ensures user IDs are ordered correctly (smaller first)', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user2, $this->user1);

        expect($conversation->user1_id)->toBe(1)
            ->and($conversation->user2_id)->toBe(2);
    });

    test('returns existing conversation instead of creating duplicate', function () {
        $first = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $second = LaravelLiveChat::getOrCreateConversation($this->user2, $this->user1);

        expect($first->id)->toBe($second->id)
            ->and(Conversation::count())->toBe(1);
    });

    test('throws exception when trying to create conversation with same user', function () {
        LaravelLiveChat::getOrCreateConversation($this->user1, $this->user1);
    })->throws(\InvalidArgumentException::class, 'Cannot create a conversation with the same user');

    test('can find existing conversation between two users', function () {
        LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        $found = LaravelLiveChat::findConversation($this->user1, $this->user2);

        expect($found)->toBeInstanceOf(Conversation::class)
            ->and($found->user1_id)->toBe(1)
            ->and($found->user2_id)->toBe(2);
    });

    test('returns null when conversation does not exist', function () {
        $found = LaravelLiveChat::findConversation($this->user1, $this->user2);

        expect($found)->toBeNull();
    });

    test('can get all conversations for a user', function () {
        LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        LaravelLiveChat::getOrCreateConversation($this->user1, $this->user3);

        $conversations = LaravelLiveChat::getUserConversations($this->user1);

        expect($conversations)->toHaveCount(2);
    });

    test('conversations are ordered by last message time', function () {
        $conv1 = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $conv2 = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user3);

        // Send message to conv1 (should make it most recent)
        LaravelLiveChat::sendMessage($conv1, $this->user1, 'Recent message');

        $conversations = LaravelLiveChat::getUserConversations($this->user1);

        expect($conversations->first()->id)->toBe($conv1->id);
    });
});

describe('Message Sending', function () {
    test('can send a message in a conversation', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        $message = LaravelLiveChat::sendMessage($conversation, $this->user1, 'Hello!');

        expect($message)->toBeInstanceOf(Message::class)
            ->and($message->conversation_id)->toBe($conversation->id)
            ->and($message->sender_id)->toBe($this->user1->id)
            ->and($message->message)->toBe('Hello!');
    });

    test('updates conversation last_message_at timestamp when message is sent', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        expect($conversation->last_message_at)->toBeNull();

        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Hello!');

        $conversation->refresh();
        expect($conversation->last_message_at)->not->toBeNull();
    });

    test('throws exception when sender is not part of conversation', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conversation, $this->user3, 'Intruder message');
    })->throws(\InvalidArgumentException::class, 'Sender is not part of this conversation');

    test('can send message using conversation ID instead of model', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        $message = LaravelLiveChat::sendMessage($conversation->id, $this->user1->id, 'Hello!');

        expect($message)->toBeInstanceOf(Message::class)
            ->and($message->conversation_id)->toBe($conversation->id);
    });
});

describe('Message Retrieval', function () {
    test('can retrieve messages from a conversation', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Message 1');
        LaravelLiveChat::sendMessage($conversation, $this->user2, 'Message 2');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Message 3');

        $messages = LaravelLiveChat::getMessages($conversation);

        expect($messages)->toHaveCount(3)
            ->and($messages->first()->message)->toBe('Message 1')
            ->and($messages->last()->message)->toBe('Message 3');
    });

    test('messages are ordered chronologically (oldest first)', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        $msg1 = LaravelLiveChat::sendMessage($conversation, $this->user1, 'First');
        // Small delay to ensure different timestamps
        usleep(1000);
        $msg2 = LaravelLiveChat::sendMessage($conversation, $this->user2, 'Second');

        $messages = LaravelLiveChat::getMessages($conversation);

        expect($messages->first()->id)->toBe($msg1->id)
            ->and($messages->last()->id)->toBe($msg2->id);
    });

    test('can get latest messages with limit', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        for ($i = 1; $i <= 10; $i++) {
            LaravelLiveChat::sendMessage($conversation, $this->user1, "Message {$i}");
        }

        $messages = LaravelLiveChat::getLatestMessages($conversation, 5);

        expect($messages)->toHaveCount(5)
            ->and($messages->first()->message)->toBe('Message 6')
            ->and($messages->last()->message)->toBe('Message 10');
    });

    test('latest messages are in chronological order', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conversation, $this->user1, 'First');
        LaravelLiveChat::sendMessage($conversation, $this->user2, 'Second');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Third');

        $messages = LaravelLiveChat::getLatestMessages($conversation);

        expect($messages->pluck('message')->toArray())->toBe(['First', 'Second', 'Third']);
    });

    test('can paginate messages', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        for ($i = 1; $i <= 60; $i++) {
            LaravelLiveChat::sendMessage($conversation, $this->user1, "Message {$i}");
        }

        $page1 = LaravelLiveChat::getMessages($conversation, 50);

        expect($page1)->toHaveCount(50)
            ->and($page1->total())->toBe(60)
            ->and($page1->hasMorePages())->toBeTrue();
    });
});

describe('Read Receipts', function () {
    test('can mark a message as read', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $message = LaravelLiveChat::sendMessage($conversation, $this->user1, 'Hello!');

        expect($message->read_at)->toBeNull();

        $updated = LaravelLiveChat::markMessageAsRead($message);

        expect($updated->read_at)->not->toBeNull();
    });

    test('can mark message as read using ID', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $message = LaravelLiveChat::sendMessage($conversation, $this->user1, 'Hello!');

        $updated = LaravelLiveChat::markMessageAsRead($message->id);

        expect($updated->read_at)->not->toBeNull();
    });

    test('can mark all messages in conversation as read for a user', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Message 1');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Message 2');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Message 3');

        $count = LaravelLiveChat::markConversationAsRead($conversation, $this->user2);

        expect($count)->toBe(3);
    });

    test('does not mark own messages as read when marking conversation as read', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conversation, $this->user1, 'From user 1');
        LaravelLiveChat::sendMessage($conversation, $this->user2, 'From user 2');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'From user 1 again');

        // User 2 marks as read - should only mark user 1's messages
        $count = LaravelLiveChat::markConversationAsRead($conversation, $this->user2);

        expect($count)->toBe(2);
    });

    test('can get unread message count for a conversation', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Unread 1');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'Unread 2');
        LaravelLiveChat::sendMessage($conversation, $this->user2, 'My own message');

        $count = LaravelLiveChat::getUnreadCount($conversation, $this->user2);

        expect($count)->toBe(2);
    });

    test('unread count does not include own messages', function () {
        $conversation = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conversation, $this->user1, 'From user 1');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'From user 1');
        LaravelLiveChat::sendMessage($conversation, $this->user1, 'From user 1');

        $count = LaravelLiveChat::getUnreadCount($conversation, $this->user1);

        expect($count)->toBe(0);
    });

    test('can get total unread count across all conversations', function () {
        $conv1 = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);
        $conv2 = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user3);

        // User 2 sends 2 messages
        LaravelLiveChat::sendMessage($conv1, $this->user2, 'Message 1');
        LaravelLiveChat::sendMessage($conv1, $this->user2, 'Message 2');

        // User 3 sends 3 messages
        LaravelLiveChat::sendMessage($conv2, $this->user3, 'Message 1');
        LaravelLiveChat::sendMessage($conv2, $this->user3, 'Message 2');
        LaravelLiveChat::sendMessage($conv2, $this->user3, 'Message 3');

        $total = LaravelLiveChat::getTotalUnreadCount($this->user1);

        expect($total)->toBe(5);
    });

    test('total unread count updates when messages are marked as read', function () {
        $conv1 = LaravelLiveChat::getOrCreateConversation($this->user1, $this->user2);

        LaravelLiveChat::sendMessage($conv1, $this->user2, 'Message 1');
        LaravelLiveChat::sendMessage($conv1, $this->user2, 'Message 2');

        expect(LaravelLiveChat::getTotalUnreadCount($this->user1))->toBe(2);

        LaravelLiveChat::markConversationAsRead($conv1, $this->user1);

        expect(LaravelLiveChat::getTotalUnreadCount($this->user1))->toBe(0);
    });
});
