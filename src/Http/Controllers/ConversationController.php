<?php

namespace muba00\LaravelLiveChat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use muba00\LaravelLiveChat\Http\Requests\StartConversationRequest;
use muba00\LaravelLiveChat\Http\Resources\ConversationResource;
use muba00\LaravelLiveChat\Models\Conversation;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', config('live-chat.pagination.conversations_per_page', 20));

        $conversations = Conversation::query()
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                    ->orWhere('user2_id', $user->id);
            })
            ->with(['user1', 'user2', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->whereNull('read_at')
                    ->where('sender_id', '!=', $user->id);
            }])
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => ConversationResource::collection($conversations->items()),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    /**
     * Get a specific conversation.
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::with(['user1', 'user2'])
            ->findOrFail($conversationId);

        // Authorization check
        if (! $conversation->includesUser($request->user())) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        return response()->json([
            'data' => new ConversationResource($conversation),
        ]);
    }

    /**
     * Start a new conversation with another user.
     */
    public function store(StartConversationRequest $request): JsonResponse
    {
        $currentUser = $request->user();
        $otherUserId = $request->validated()['user_id'];

        // Prevent user from starting a conversation with themselves
        if ($currentUser->id === $otherUserId) {
            return response()->json([
                'message' => 'Cannot start a conversation with yourself.',
            ], 422);
        }

        // Check if conversation already exists (either direction)
        $existingConversation = Conversation::query()
            ->where(function ($query) use ($currentUser, $otherUserId) {
                $query->where('user1_id', $currentUser->id)
                    ->where('user2_id', $otherUserId);
            })
            ->orWhere(function ($query) use ($currentUser, $otherUserId) {
                $query->where('user1_id', $otherUserId)
                    ->where('user2_id', $currentUser->id);
            })
            ->first();

        if ($existingConversation) {
            return response()->json([
                'data' => new ConversationResource($existingConversation->load(['user1', 'user2'])),
                'message' => 'Conversation already exists.',
            ]);
        }

        // Create new conversation
        $conversation = Conversation::create([
            'user1_id' => min($currentUser->id, $otherUserId),
            'user2_id' => max($currentUser->id, $otherUserId),
        ]);

        return response()->json([
            'data' => new ConversationResource($conversation->load(['user1', 'user2'])),
            'message' => 'Conversation created successfully.',
        ], 201);
    }

    /**
     * Delete a conversation.
     */
    public function destroy(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorization check
        if (! $conversation->includesUser($request->user())) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        // Delete all messages in the conversation
        DB::transaction(function () use ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        });

        return response()->json([
            'message' => 'Conversation deleted successfully.',
        ]);
    }
}
