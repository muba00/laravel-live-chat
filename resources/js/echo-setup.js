/**
 * Laravel Live Chat - Echo Setup
 * 
 * This file provides a helper to initialize Laravel Echo with Reverb
 * for the Live Chat package.
 * 
 * @requires laravel-echo
 * @requires pusher-js
 */

/**
 * Initialize Laravel Echo for Live Chat
 * 
 * @param {Object} config - Configuration options
 * @param {string} config.key - Reverb app key
 * @param {string} config.wsHost - WebSocket host (default: window.location.hostname)
 * @param {number} config.wsPort - WebSocket port (default: 8080)
 * @param {number} config.wssPort - WebSocket secure port (default: 443)
 * @param {string} config.scheme - Protocol scheme (default: 'https')
 * @param {boolean} config.forceTLS - Force TLS (default: true)
 * @param {Array<string>} config.enabledTransports - Enabled transports (default: ['ws', 'wss'])
 * @returns {Echo|null} Echo instance or null if Echo is not available
 */
export function initializeLiveChatEcho(config = {}) {
    // Check if Echo is available
    if (typeof window.Echo !== 'undefined') {
        console.warn('Laravel Echo is already initialized. Skipping Live Chat Echo initialization.');
        return window.Echo;
    }

    // Check if required dependencies are available
    if (typeof Echo === 'undefined') {
        console.error('Laravel Echo is not loaded. Please install: npm install --save-dev laravel-echo pusher-js');
        return null;
    }

    if (typeof Pusher === 'undefined') {
        console.error('Pusher is not loaded. Please install: npm install --save-dev pusher-js');
        return null;
    }

    // Default configuration
    const defaultConfig = {
        broadcaster: 'reverb',
        key: config.key || import.meta.env?.VITE_REVERB_APP_KEY || '',
        wsHost: config.wsHost || import.meta.env?.VITE_REVERB_HOST || window.location.hostname,
        wsPort: config.wsPort || import.meta.env?.VITE_REVERB_PORT || 8080,
        wssPort: config.wssPort || import.meta.env?.VITE_REVERB_PORT || 443,
        forceTLS: config.forceTLS !== undefined ? config.forceTLS : (import.meta.env?.VITE_REVERB_SCHEME || 'https') === 'https',
        enabledTransports: config.enabledTransports || ['ws', 'wss'],
    };

    // Merge with custom config
    const finalConfig = { ...defaultConfig, ...config };

    // Validate required key
    if (!finalConfig.key) {
        console.error('Reverb app key is required. Please provide it in config or set VITE_REVERB_APP_KEY in your .env file.');
        return null;
    }

    try {
        // Set Pusher on window for Echo
        window.Pusher = Pusher;

        // Initialize Echo
        window.Echo = new Echo(finalConfig);

        console.log('Laravel Echo initialized successfully for Live Chat');

        return window.Echo;
    } catch (error) {
        console.error('Failed to initialize Laravel Echo:', error);
        return null;
    }
}

/**
 * Subscribe to a conversation channel
 * 
 * @param {number} conversationId - The conversation ID
 * @param {Object} callbacks - Event callbacks
 * @param {Function} callbacks.onMessageSent - Called when a message is sent
 * @param {Function} callbacks.onUserTyping - Called when a user is typing
 * @param {string} channelPrefix - Channel prefix (default: 'chat')
 * @returns {Object|null} Channel subscription or null if Echo is not available
 */
export function subscribeToConversation(conversationId, callbacks = {}, channelPrefix = 'chat') {
    if (typeof window.Echo === 'undefined') {
        console.error('Laravel Echo is not initialized. Call initializeLiveChatEcho() first.');
        return null;
    }

    const channelName = `${channelPrefix}.${conversationId}`;

    const channel = window.Echo.private(channelName);

    // Listen for message sent event
    if (callbacks.onMessageSent) {
        channel.listen('.MessageSent', callbacks.onMessageSent);
    }

    // Listen for user typing event
    if (callbacks.onUserTyping) {
        channel.listen('.UserTyping', callbacks.onUserTyping);
    }

    // Listen for typing whispers (client events)
    if (callbacks.onTypingWhisper) {
        channel.listenForWhisper('typing', callbacks.onTypingWhisper);
    }

    console.log(`Subscribed to conversation channel: ${channelName}`);

    return channel;
}

/**
 * Unsubscribe from a conversation channel
 * 
 * @param {number} conversationId - The conversation ID
 * @param {string} channelPrefix - Channel prefix (default: 'chat')
 */
export function unsubscribeFromConversation(conversationId, channelPrefix = 'chat') {
    if (typeof window.Echo === 'undefined') {
        console.warn('Laravel Echo is not initialized.');
        return;
    }

    const channelName = `${channelPrefix}.${conversationId}`;
    window.Echo.leave(channelName);

    console.log(`Unsubscribed from conversation channel: ${channelName}`);
}

/**
 * Broadcast typing indicator
 * 
 * @param {number} conversationId - The conversation ID
 * @param {number} userId - The current user ID
 * @param {boolean} isTyping - Whether the user is typing
 * @param {string} channelPrefix - Channel prefix (default: 'chat')
 */
export function broadcastTyping(conversationId, userId, isTyping, channelPrefix = 'chat') {
    if (typeof window.Echo === 'undefined') {
        console.warn('Laravel Echo is not initialized.');
        return;
    }

    const channelName = `${channelPrefix}.${conversationId}`;

    window.Echo.private(channelName).whisper('typing', {
        user_id: userId,
        is_typing: isTyping
    });
}

// Auto-initialize if running in browser with meta tags
if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        // Check if auto-init is enabled via meta tag
        const autoInit = document.querySelector('meta[name="live-chat-auto-init"]');

        if (autoInit && autoInit.content === 'true') {
            // Get config from meta tags
            const config = {
                key: document.querySelector('meta[name="reverb-app-key"]')?.content,
                wsHost: document.querySelector('meta[name="reverb-host"]')?.content,
                wsPort: parseInt(document.querySelector('meta[name="reverb-port"]')?.content) || undefined,
                wssPort: parseInt(document.querySelector('meta[name="reverb-port"]')?.content) || undefined,
                forceTLS: document.querySelector('meta[name="reverb-scheme"]')?.content === 'https',
            };

            initializeLiveChatEcho(config);
        }
    });
}
