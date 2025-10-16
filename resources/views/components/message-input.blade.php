@props(['conversation', 'currentUser', 'placeholder' => 'Type a message...'])

<form {{ $attributes->merge(['class' => 'live-chat-form']) }} 
      id="live-chat-form-{{ $conversation->id }}"
      data-conversation-id="{{ $conversation->id }}"
      data-user-id="{{ $currentUser->id }}">
    @csrf
    
    <div class="live-chat-input-wrapper">
        <textarea 
            name="message" 
            id="live-chat-input-{{ $conversation->id }}"
            class="live-chat-input" 
            placeholder="{{ $placeholder }}"
            rows="1"
            required></textarea>
        
        <button type="submit" class="live-chat-send-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
        </button>
    </div>
</form>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const conversationId = {{ $conversation->id }};
                const currentUserId = {{ $currentUser->id }};
                const form = document.getElementById('live-chat-form-' + conversationId);
                const input = document.getElementById('live-chat-input-' + conversationId);
                
                if (!form || !input) return;

                // Handle form submission
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const message = input.value.trim();
                    if (!message) return;

                    try {
                        // Send message to backend
                        const response = await fetch('/api/chat/conversations/' + conversationId + '/messages', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ message })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            
                            // Add message to DOM immediately for sender
                            const messagesContainer = document.getElementById('live-chat-messages-' + conversationId);
                            if (messagesContainer) {
                                const messageHtml = `
                                    <div class="live-chat-message live-chat-message-sent" data-message-id="${data.id}">
                                        <div class="live-chat-message-content">
                                            <div class="live-chat-message-text">${data.message}</div>
                                            <div class="live-chat-message-meta">
                                                <span class="live-chat-message-time">just now</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                            
                            // Clear input
                            input.value = '';
                            input.style.height = 'auto';
                        } else {
                            console.error('Failed to send message:', await response.text());
                        }
                    } catch (error) {
                        console.error('Error sending message:', error);
                    }
                });

                // Auto-resize textarea
                input.addEventListener('input', () => {
                    input.style.height = 'auto';
                    input.style.height = input.scrollHeight + 'px';
                });

                // Typing indicator
                let typingTimeout;
                input.addEventListener('input', () => {
                    clearTimeout(typingTimeout);
                    
                    // Broadcast typing start
                    if (typeof window.Echo !== 'undefined') {
                        window.Echo.private('{{ config('live-chat.broadcasting.channel_prefix', 'chat') }}.' + conversationId)
                            .whisper('typing', {
                                user_id: currentUserId,
                                is_typing: true
                            });
                    }

                    // Broadcast typing stop after 1 second of inactivity
                    typingTimeout = setTimeout(() => {
                        if (typeof window.Echo !== 'undefined') {
                            window.Echo.private('{{ config('live-chat.broadcasting.channel_prefix', 'chat') }}.' + conversationId)
                                .whisper('typing', {
                                    user_id: currentUserId,
                                    is_typing: false
                                });
                        }
                    }, 1000);
                });

                // Listen for typing whispers (client events)
                if (typeof window.Echo !== 'undefined') {
                    window.Echo.private('{{ config('live-chat.broadcasting.channel_prefix', 'chat') }}.' + conversationId)
                        .listenForWhisper('typing', (e) => {
                            window.dispatchEvent(new CustomEvent('live-chat:user-typing', { 
                                detail: e 
                            }));
                        });
                }
            });
        </script>
    @endpush
@endonce
