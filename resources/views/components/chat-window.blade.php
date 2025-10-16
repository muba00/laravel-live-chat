@props(['conversation', 'currentUser'])

<div {{ $attributes->merge(['class' => 'live-chat-window']) }}>
    <div class="live-chat-header">
        <h3 class="live-chat-title">
            @if($conversation)
                Chat with {{ $conversation->getOtherUser($currentUser)->name ?? 'User' }}
            @else
                New Conversation
            @endif
        </h3>
    </div>

    <div class="live-chat-body">
        @if($conversation)
            <x-live-chat::message-list :conversation="$conversation" :currentUser="$currentUser" />
        @else
            <div class="live-chat-empty">
                <p>No messages yet. Start a conversation!</p>
            </div>
        @endif
    </div>

    <div class="live-chat-footer">
        @if($conversation)
            <x-live-chat::message-input :conversation="$conversation" :currentUser="$currentUser" />
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script>
            // Initialize Echo connection for this conversation
            @if($conversation)
            const conversationId = {{ $conversation->id }};
            const currentUserId = {{ $currentUser->id }};
            
            if (typeof window.Echo !== 'undefined') {
                window.Echo.private(`{{ config('live-chat.broadcasting.channel_prefix', 'chat') }}.${conversationId}`)
                    .listen('.MessageSent', (e) => {
                        console.log('New message received:', e);
                        // Dispatch custom event for message handling
                        window.dispatchEvent(new CustomEvent('live-chat:message-received', { 
                            detail: e 
                        }));
                    })
                    .listen('.UserTyping', (e) => {
                        console.log('User typing:', e);
                        // Dispatch custom event for typing indicator
                        window.dispatchEvent(new CustomEvent('live-chat:user-typing', { 
                            detail: e 
                        }));
                    });
            }
            @endif
        </script>
    @endpush
@endonce
