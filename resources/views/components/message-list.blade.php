@props(['conversation', 'currentUser', 'limit' => 50])

@php
    $messages = $conversation->messages()
        ->latest()
        ->limit($limit)
        ->get()
        ->reverse();
@endphp

<div {{ $attributes->merge(['class' => 'live-chat-messages']) }} id="live-chat-messages-{{ $conversation->id }}">
    @forelse($messages as $message)
        <div class="live-chat-message {{ $message->sender_id === $currentUser->id ? 'live-chat-message-sent' : 'live-chat-message-received' }}" 
             data-message-id="{{ $message->id }}">
            <div class="live-chat-message-content">
                <div class="live-chat-message-text">
                    {{ $message->message }}
                </div>
                <div class="live-chat-message-meta">
                    <span class="live-chat-message-time">
                        {{ $message->created_at->diffForHumans() }}
                    </span>
                    @if($message->sender_id === $currentUser->id && $message->isRead())
                        <span class="live-chat-message-status">
                            ✓✓
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="live-chat-no-messages">
            <p>No messages yet</p>
        </div>
    @endforelse
</div>

<div class="live-chat-typing-indicator" id="typing-indicator-{{ $conversation->id }}" style="display: none;">
    <span class="live-chat-typing-dots">
        <span></span>
        <span></span>
        <span></span>
    </span>
    <span class="live-chat-typing-text">typing...</span>
</div>

@once
    @push('scripts')
        <script>
            // Auto-scroll to bottom when new messages arrive
            function scrollToBottom(conversationId) {
                const messagesContainer = document.getElementById('live-chat-messages-' + conversationId);
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }

            // Listen for new messages
            window.addEventListener('live-chat:message-received', (event) => {
                const message = event.detail;
                const conversationId = {{ $conversation->id }};
                
                // Add message to DOM
                const messagesContainer = document.getElementById('live-chat-messages-' + conversationId);
                if (messagesContainer && message.conversation_id == conversationId) {
                    const messageHtml = `
                        <div class="live-chat-message live-chat-message-received" data-message-id="${message.id}">
                            <div class="live-chat-message-content">
                                <div class="live-chat-message-text">${message.message}</div>
                                <div class="live-chat-message-meta">
                                    <span class="live-chat-message-time">just now</span>
                                </div>
                            </div>
                        </div>
                    `;
                    messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
                    scrollToBottom(conversationId);
                }
            });

            // Handle typing indicator
            window.addEventListener('live-chat:user-typing', (event) => {
                const data = event.detail;
                const conversationId = {{ $conversation->id }};
                const currentUserId = {{ $currentUser->id }};
                const typingIndicator = document.getElementById('typing-indicator-' + conversationId);
                
                if (typingIndicator && data.user_id != currentUserId) {
                    typingIndicator.style.display = data.is_typing ? 'block' : 'none';
                    
                    // Auto-hide after 3 seconds
                    if (data.is_typing) {
                        setTimeout(() => {
                            typingIndicator.style.display = 'none';
                        }, 3000);
                    }
                }
            });

            // Scroll to bottom on initial load
            document.addEventListener('DOMContentLoaded', () => {
                scrollToBottom({{ $conversation->id }});
            });
        </script>
    @endpush
@endonce
