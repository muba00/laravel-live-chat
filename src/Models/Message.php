<?php

namespace muba00\LaravelLiveChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Conversation $conversation
 * @property-read \Illuminate\Database\Eloquent\Model $sender
 */
class Message extends Model
{
    use HasFactory;

    protected $table = 'live_chat_messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(
            config('live-chat.user_model', 'App\Models\User'),
            'sender_id'
        );
    }

    /**
     * Check if the message has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if the message is read by a specific user.
     */
    public function isReadBy(mixed $user): bool
    {
        $userId = is_int($user) ? $user : (is_object($user) && isset($user->id) ? (int) $user->id : null);

        if ($userId === null) {
            return false;
        }

        // A message is considered read by a user if:
        // 1. The user is not the sender
        // 2. The read_at timestamp is set
        if ($this->sender_id === $userId) {
            return true; // Sender always "reads" their own messages
        }

        return $this->isRead();
    }

    /**
     * Mark the message as read.
     */
    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Scope to filter unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to filter read messages.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to filter messages by sender.
     */
    public function scopeBySender($query, mixed $user)
    {
        $userId = is_int($user) ? $user : (is_object($user) && isset($user->id) ? (int) $user->id : null);

        if ($userId === null) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query->where('sender_id', $userId);
    }

    /**
     * Scope to filter messages not sent by a specific user.
     */
    public function scopeNotBySender($query, mixed $user)
    {
        $userId = is_int($user) ? $user : (is_object($user) && isset($user->id) ? (int) $user->id : null);

        if ($userId === null) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query->where('sender_id', '!=', $userId);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \muba00\LaravelLiveChat\Database\Factories\MessageFactory::new();
    }
}
