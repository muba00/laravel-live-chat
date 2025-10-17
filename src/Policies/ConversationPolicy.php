<?php

namespace muba00\LaravelLiveChat\Policies;

use Illuminate\Database\Eloquent\Model;
use muba00\LaravelLiveChat\Models\Conversation;

class ConversationPolicy
{
    /**
     * Determine if the user can view the conversation.
     */
    public function view(?Model $user, Conversation $conversation): bool
    {
        if (! $user) {
            return false;
        }

        return $conversation->includesUser($user);
    }

    /**
     * Determine if the user can send messages in the conversation.
     */
    public function sendMessage(?Model $user, Conversation $conversation): bool
    {
        if (! $user) {
            return false;
        }

        return $conversation->includesUser($user);
    }

    /**
     * Determine if the user can mark messages as read in the conversation.
     */
    public function markAsRead(?Model $user, Conversation $conversation): bool
    {
        if (! $user) {
            return false;
        }

        return $conversation->includesUser($user);
    }

    /**
     * Determine if the user can send typing indicators in the conversation.
     */
    public function sendTypingIndicator(?Model $user, Conversation $conversation): bool
    {
        if (! $user) {
            return false;
        }

        return $conversation->includesUser($user);
    }
}
