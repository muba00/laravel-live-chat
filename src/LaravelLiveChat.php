<?php

namespace muba00\LaravelLiveChat;

use Illuminate\Database\Eloquent\Model;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

class LaravelLiveChat
{
    /**
     * Extract user ID from mixed input.
     */
    protected function getUserId(mixed $user): int
    {
        if (is_int($user)) {
            return $user;
        }

        if (is_object($user) && isset($user->id)) {
            return (int) $user->id;
        }

        throw new \InvalidArgumentException('Invalid user parameter');
    }

    /**
     * Get or create a conversation between two users.
     *
     * @param  Model|object|int  $user1
     * @param  Model|object|int  $user2
     */
    public function getOrCreateConversation(mixed $user1, mixed $user2): Conversation
    {
        $user1Id = $this->getUserId($user1);
        $user2Id = $this->getUserId($user2);

        // Ensure users are different
        if ($user1Id === $user2Id) {
            throw new \InvalidArgumentException('Cannot create a conversation with the same user');
        }

        // Ensure user1_id is always smaller than user2_id for consistency
        [$smallerId, $largerId] = $user1Id < $user2Id
            ? [$user1Id, $user2Id]
            : [$user2Id, $user1Id];

        // Find or create the conversation
        return Conversation::firstOrCreate([
            'user1_id' => $smallerId,
            'user2_id' => $largerId,
        ]);
    }

    /**
     * Find an existing conversation between two users.
     *
     * @param  Model|object|int  $user1
     * @param  Model|object|int  $user2
     */
    public function findConversation(mixed $user1, mixed $user2): ?Conversation
    {
        $user1Id = $this->getUserId($user1);
        $user2Id = $this->getUserId($user2);

        // Ensure user1_id is always smaller than user2_id
        [$smallerId, $largerId] = $user1Id < $user2Id
            ? [$user1Id, $user2Id]
            : [$user2Id, $user1Id];

        return Conversation::where('user1_id', $smallerId)
            ->where('user2_id', $largerId)
            ->first();
    }

    /**
     * Get all conversations for a user.
     *
     * @param  Model|object|int  $user
     */
    public function getUserConversations(mixed $user): \Illuminate\Database\Eloquent\Collection
    {
        $userId = $this->getUserId($user);

        return Conversation::forUser($userId)
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Send a message in a conversation.
     *
     * @param  Conversation|int  $conversation
     * @param  Model|object|int  $sender
     */
    public function sendMessage(mixed $conversation, mixed $sender, string $message): Message
    {
        $conversationId = $conversation instanceof Conversation ? $conversation->id : $conversation;
        $senderId = $this->getUserId($sender);

        // Load conversation if needed
        if (! $conversation instanceof Conversation) {
            $conversation = Conversation::findOrFail($conversationId);
        }

        // Validate sender is part of the conversation
        if (! $conversation->includesUser($senderId)) {
            throw new \InvalidArgumentException('Sender is not part of this conversation');
        }

        // Create the message
        $newMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'message' => $message,
        ]);

        // Update conversation's last message timestamp
        $conversation->updateLastMessageTime();

        return $newMessage;
    }

    /**
     * Get messages from a conversation with pagination.
     *
     * @param  Conversation|int  $conversation
     */
    public function getMessages(mixed $conversation, int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $conversationId = $conversation instanceof Conversation ? $conversation->id : $conversation;

        return Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get latest messages from a conversation (useful for initial load).
     *
     * @param  Conversation|int  $conversation
     */
    public function getLatestMessages(mixed $conversation, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $conversationId = $conversation instanceof Conversation ? $conversation->id : $conversation;

        return Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Mark a message as read.
     *
     * @param  Message|int  $message
     */
    public function markMessageAsRead(mixed $message): Message
    {
        if (! $message instanceof Message) {
            $message = Message::findOrFail($message);
        }

        $message->markAsRead();

        return $message->fresh();
    }

    /**
     * Mark all messages in a conversation as read for a specific user.
     *
     * @param  Conversation|int  $conversation
     * @param  Model|object|int  $user
     */
    public function markConversationAsRead(mixed $conversation, mixed $user): int
    {
        $conversationId = $conversation instanceof Conversation ? $conversation->id : $conversation;
        $userId = $this->getUserId($user);

        // Mark all unread messages that were NOT sent by this user
        return Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread message count for a user in a conversation.
     *
     * @param  Conversation|int  $conversation
     * @param  Model|object|int  $user
     */
    public function getUnreadCount(mixed $conversation, mixed $user): int
    {
        $conversationId = $conversation instanceof Conversation ? $conversation->id : $conversation;
        $userId = $this->getUserId($user);

        return Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get total unread message count for a user across all conversations.
     *
     * @param  Model|object|int  $user
     */
    public function getTotalUnreadCount(mixed $user): int
    {
        $userId = $this->getUserId($user);

        // Get all conversations for the user
        $conversationIds = Conversation::forUser($userId)
            ->pluck('id');

        // Count unread messages in those conversations (not sent by the user)
        return Message::whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
