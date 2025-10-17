<?php

namespace muba00\LaravelLiveChat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \muba00\LaravelLiveChat\Models\Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();

        return [
            'id' => $this->id,
            'user1_id' => $this->user1_id,
            'user2_id' => $this->user2_id,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'other_user' => $this->whenLoaded('user1', function () use ($currentUser) {
                $otherUser = $this->getOtherUser($currentUser);

                return $otherUser ? [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name ?? null,
                    'email' => $otherUser->email ?? null,
                ] : null;
            }),
            'latest_message' => $this->whenLoaded('messages', function () {
                $latestMessage = $this->messages->first();

                return $latestMessage ? new MessageResource($latestMessage) : null;
            }),
            'unread_count' => $this->when(
                isset($this->unread_count),
                $this->unread_count ?? 0
            ),
        ];
    }
}
