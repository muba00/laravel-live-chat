<?php

namespace muba00\LaravelLiveChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user1_id
 * @property int $user2_id
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model $user1
 * @property-read \Illuminate\Database\Eloquent\Model $user2
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Message> $messages
 */
class Conversation extends Model
{
    use HasFactory;

    protected $table = 'live_chat_conversations';

    protected $fillable = [
        'user1_id',
        'user2_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the first user in the conversation.
     */
    public function user1(): BelongsTo
    {
        return $this->belongsTo(
            config('live-chat.user_model', 'App\Models\User'),
            'user1_id'
        );
    }

    /**
     * Get the second user in the conversation.
     */
    public function user2(): BelongsTo
    {
        return $this->belongsTo(
            config('live-chat.user_model', 'App\Models\User'),
            'user2_id'
        );
    }

    /**
     * Get all messages in the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * Get the latest message in the conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->messages()->latest();
    }

    /**
     * Get the other user in the conversation.
     */
    public function getOtherUser(mixed $currentUser): mixed
    {
        $userId = $currentUser instanceof Model ? $currentUser->id : $currentUser->id;

        if ($this->user1_id === $userId) {
            return $this->user2;
        }

        if ($this->user2_id === $userId) {
            return $this->user1;
        }

        return null;
    }

    /**
     * Check if the conversation includes a specific user.
     */
    public function includesUser(mixed $user): bool
    {
        $userId = $user instanceof Model ? $user->id : $user->id;

        return $this->user1_id === $userId || $this->user2_id === $userId;
    }

    /**
     * Scope to filter conversations for a specific user.
     */
    public function scopeForUser($query, mixed $user)
    {
        $userId = $user instanceof Model ? $user->id : $user->id;

        return $query->where(function ($q) use ($userId) {
            $q->where('user1_id', $userId)
                ->orWhere('user2_id', $userId);
        });
    }

    /**
     * Scope to find conversation between two users.
     */
    public function scopeBetweenUsers($query, mixed $user1, mixed $user2)
    {
        $user1Id = $user1 instanceof Model ? $user1->id : $user1->id;
        $user2Id = $user2 instanceof Model ? $user2->id : $user2->id;

        $ids = collect([$user1Id, $user2Id])->sort()->values();

        return $query->where('user1_id', $ids[0])
            ->where('user2_id', $ids[1]);
    }

    /**
     * Update the last message timestamp.
     */
    public function updateLastMessageTime(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \muba00\LaravelLiveChat\Database\Factories\ConversationFactory::new();
    }
}
