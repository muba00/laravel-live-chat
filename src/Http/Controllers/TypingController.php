<?php

namespace muba00\LaravelLiveChat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use muba00\LaravelLiveChat\Events\UserTyping;
use muba00\LaravelLiveChat\Http\Requests\TypingIndicatorRequest;
use muba00\LaravelLiveChat\Models\Conversation;

class TypingController extends Controller
{
    /**
     * Send a typing indicator.
     */
    public function store(TypingIndicatorRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        // Authorization check
        if (! $conversation->includesUser($user)) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $isTyping = $request->validated()['is_typing'];

        // Broadcast typing indicator
        if (config('live-chat.broadcasting.enabled', true) && config('live-chat.features.typing_indicators', true)) {
            broadcast(new UserTyping($conversationId, $user->id, $isTyping))->toOthers();
        }

        return response()->json([
            'message' => 'Typing indicator sent successfully.',
        ]);
    }
}
