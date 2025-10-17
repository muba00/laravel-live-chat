<?php

namespace muba00\LaravelLiveChat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use muba00\LaravelLiveChat\Events\MessageSent;
use muba00\LaravelLiveChat\Http\Requests\MarkMessagesAsReadRequest;
use muba00\LaravelLiveChat\Http\Requests\SendMessageRequest;
use muba00\LaravelLiveChat\Http\Resources\MessageResource;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

class MessageController extends Controller
{
    /**
     * Get all messages in a conversation.
     */
    public function index(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorization check
        if (! $conversation->includesUser($request->user())) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $perPage = $request->input('per_page', config('live-chat.pagination.messages_per_page', 50));

        $messages = Message::query()
            ->where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => MessageResource::collection($messages->items()),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function store(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Authorization check
        if (! $conversation->includesUser($user)) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'message' => $request->validated()['message'],
        ]);

        // Update conversation's last_message_at timestamp
        $conversation->update([
            'last_message_at' => $message->created_at,
        ]);

        // Broadcast the message
        if (config('live-chat.broadcasting.enabled', true)) {
            broadcast(new MessageSent($message->load('sender'), $conversation))->toOthers();
        }

        return response()->json([
            'data' => new MessageResource($message->load('sender')),
            'message' => 'Message sent successfully.',
        ], 201);
    }

    /**
     * Mark messages as read in a conversation.
     */
    public function markAsRead(MarkMessagesAsReadRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Authorization check
        if (! $conversation->includesUser($user)) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $validated = $request->validated();

        if (isset($validated['message_ids']) && is_array($validated['message_ids'])) {
            // Mark specific messages as read
            $messages = Message::query()
                ->where('conversation_id', $conversationId)
                ->whereIn('id', $validated['message_ids'])
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->get();

            foreach ($messages as $message) {
                $message->markAsRead();
            }

            $count = $messages->count();
        } else {
            // Mark all unread messages in the conversation as read
            $count = Message::query()
                ->where('conversation_id', $conversationId)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => "Marked {$count} message(s) as read.",
            'count' => $count,
        ]);
    }

    /**
     * Get unread message count for a conversation.
     */
    public function unreadCount(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Authorization check
        if (! $conversation->includesUser($user)) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $count = Message::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
