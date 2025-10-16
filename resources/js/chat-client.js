/**
 * Laravel Live Chat - Vanilla JavaScript Client
 * 
 * A framework-agnostic client for handling live chat functionality.
 * Can be used without any frontend framework.
 */

export class LiveChatClient {
    constructor(options = {}) {
        this.conversationId = options.conversationId;
        this.currentUserId = options.currentUserId;
        this.channelPrefix = options.channelPrefix || 'chat';
        this.apiBaseUrl = options.apiBaseUrl || '/api/chat';
        this.csrfToken = options.csrfToken || this.getCsrfToken();

        // DOM elements
        this.messagesContainer = null;
        this.inputElement = null;
        this.formElement = null;
        this.typingIndicator = null;

        // State
        this.isTyping = false;
        this.typingTimeout = null;
        this.channel = null;

        // Callbacks
        this.onMessageReceived = options.onMessageReceived || null;
        this.onMessageSent = options.onMessageSent || null;
        this.onTyping = options.onTyping || null;
        this.onError = options.onError || null;
    }

    /**
     * Get CSRF token from meta tag
     */
    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.content : '';
    }

    /**
     * Initialize the chat client
     */
    init(elements = {}) {
        this.messagesContainer = elements.messagesContainer || document.getElementById(`live-chat-messages-${this.conversationId}`);
        this.inputElement = elements.inputElement || document.getElementById(`live-chat-input-${this.conversationId}`);
        this.formElement = elements.formElement || document.getElementById(`live-chat-form-${this.conversationId}`);
        this.typingIndicator = elements.typingIndicator || document.getElementById(`typing-indicator-${this.conversationId}`);

        if (!this.messagesContainer || !this.inputElement || !this.formElement) {
            console.error('Required DOM elements not found');
            return false;
        }

        this.setupEventListeners();
        this.subscribeToChannel();
        this.scrollToBottom();

        return true;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Form submission
        this.formElement.addEventListener('submit', (e) => this.handleSubmit(e));

        // Input events for typing indicator
        this.inputElement.addEventListener('input', () => this.handleInput());

        // Auto-resize textarea
        this.inputElement.addEventListener('input', () => this.autoResizeTextarea());
    }

    /**
     * Subscribe to the conversation channel
     */
    subscribeToChannel() {
        if (typeof window.Echo === 'undefined') {
            console.warn('Laravel Echo is not initialized. Real-time features will not work.');
            return;
        }

        const channelName = `${this.channelPrefix}.${this.conversationId}`;
        this.channel = window.Echo.private(channelName);

        // Listen for new messages
        this.channel.listen('.MessageSent', (e) => {
            this.handleMessageReceived(e);
        });

        // Listen for typing indicators
        this.channel.listenForWhisper('typing', (e) => {
            this.handleTypingEvent(e);
        });

        console.log(`Subscribed to channel: ${channelName}`);
    }

    /**
     * Handle form submission
     */
    async handleSubmit(e) {
        e.preventDefault();

        const message = this.inputElement.value.trim();
        if (!message) return;

        // Disable input while sending
        this.inputElement.disabled = true;

        try {
            const response = await this.sendMessage(message);

            if (response) {
                // Add message to UI
                this.addMessage({
                    id: response.id,
                    message: response.message,
                    sender_id: this.currentUserId,
                    created_at: response.created_at,
                    isSent: true
                });

                // Clear input
                this.inputElement.value = '';
                this.autoResizeTextarea();
                this.scrollToBottom();

                // Callback
                if (this.onMessageSent) {
                    this.onMessageSent(response);
                }
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            if (this.onError) {
                this.onError(error);
            }
        } finally {
            this.inputElement.disabled = false;
            this.inputElement.focus();
        }
    }

    /**
     * Send message to backend
     */
    async sendMessage(message) {
        const url = `${this.apiBaseUrl}/conversations/${this.conversationId}/messages`;

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Handle received message
     */
    handleMessageReceived(data) {
        // Don't add if it's from current user (already added when sent)
        if (data.sender_id === this.currentUserId) {
            return;
        }

        this.addMessage({
            id: data.id,
            message: data.message,
            sender_id: data.sender_id,
            created_at: data.created_at,
            isSent: false
        });

        this.scrollToBottom();

        // Callback
        if (this.onMessageReceived) {
            this.onMessageReceived(data);
        }
    }

    /**
     * Add message to the DOM
     */
    addMessage(messageData) {
        const messageClass = messageData.isSent ? 'live-chat-message-sent' : 'live-chat-message-received';

        const messageHtml = `
            <div class="live-chat-message ${messageClass}" data-message-id="${messageData.id}">
                <div class="live-chat-message-content">
                    <div class="live-chat-message-text">${this.escapeHtml(messageData.message)}</div>
                    <div class="live-chat-message-meta">
                        <span class="live-chat-message-time">just now</span>
                    </div>
                </div>
            </div>
        `;

        this.messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
    }

    /**
     * Handle input for typing indicator
     */
    handleInput() {
        clearTimeout(this.typingTimeout);

        if (!this.isTyping) {
            this.isTyping = true;
            this.broadcastTyping(true);
        }

        // Stop typing after 1 second of inactivity
        this.typingTimeout = setTimeout(() => {
            this.isTyping = false;
            this.broadcastTyping(false);
        }, 1000);
    }

    /**
     * Broadcast typing status
     */
    broadcastTyping(isTyping) {
        if (!this.channel) return;

        this.channel.whisper('typing', {
            user_id: this.currentUserId,
            is_typing: isTyping
        });
    }

    /**
     * Handle typing event from other users
     */
    handleTypingEvent(data) {
        // Ignore own typing
        if (data.user_id === this.currentUserId) {
            return;
        }

        if (this.typingIndicator) {
            this.typingIndicator.style.display = data.is_typing ? 'block' : 'none';

            // Auto-hide after 3 seconds
            if (data.is_typing) {
                setTimeout(() => {
                    this.typingIndicator.style.display = 'none';
                }, 3000);
            }
        }

        // Callback
        if (this.onTyping) {
            this.onTyping(data);
        }
    }

    /**
     * Auto-resize textarea based on content
     */
    autoResizeTextarea() {
        this.inputElement.style.height = 'auto';
        this.inputElement.style.height = this.inputElement.scrollHeight + 'px';
    }

    /**
     * Scroll messages to bottom
     */
    scrollToBottom() {
        if (this.messagesContainer) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Load more messages
     */
    async loadMoreMessages(before = null) {
        const url = `${this.apiBaseUrl}/conversations/${this.conversationId}/messages${before ? `?before=${before}` : ''}`;

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Failed to load messages:', error);
            if (this.onError) {
                this.onError(error);
            }
            return null;
        }
    }

    /**
     * Mark conversation as read
     */
    async markAsRead() {
        const url = `${this.apiBaseUrl}/conversations/${this.conversationId}/mark-as-read`;

        try {
            await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    }

    /**
     * Destroy the client and cleanup
     */
    destroy() {
        // Unsubscribe from channel
        if (this.channel && typeof window.Echo !== 'undefined') {
            window.Echo.leave(`${this.channelPrefix}.${this.conversationId}`);
        }

        // Clear timeouts
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        // Remove event listeners
        if (this.formElement) {
            this.formElement.removeEventListener('submit', this.handleSubmit);
        }

        console.log('LiveChatClient destroyed');
    }
}

// Export for use in browser
if (typeof window !== 'undefined') {
    window.LiveChatClient = LiveChatClient;
}
