# Vue 3 Chat Component Example

This example shows how to integrate Laravel Live Chat with Vue 3 using the Composition API.

## Installation

```bash
npm install --save-dev vue@^3 laravel-echo pusher-js
```

## Setup Laravel Echo

First, set up Laravel Echo in your `resources/js/bootstrap.js`:

```javascript
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});
```

## Chat Component

Create `resources/js/components/LiveChat.vue`:

```vue
<template>
    <div class="live-chat-window">
        <!-- Header -->
        <div class="live-chat-header">
            <h3 class="live-chat-title">
                {{ otherUser?.name || "Chat" }}
            </h3>
            <span v-if="isOnline" class="online-indicator">●</span>
        </div>

        <!-- Messages -->
        <div ref="messagesContainer" class="live-chat-messages">
            <div
                v-for="message in messages"
                :key="message.id"
                :class="[
                    'live-chat-message',
                    message.isSent
                        ? 'live-chat-message-sent'
                        : 'live-chat-message-received',
                ]"
            >
                <div class="live-chat-message-content">
                    <div class="live-chat-message-text">
                        {{ message.message }}
                    </div>
                    <div class="live-chat-message-meta">
                        <span class="live-chat-message-time">{{
                            formatTime(message.created_at)
                        }}</span>
                        <span
                            v-if="message.isSent && message.read_at"
                            class="live-chat-message-status"
                            >✓✓</span
                        >
                    </div>
                </div>
            </div>

            <!-- Typing Indicator -->
            <div v-if="isTyping" class="live-chat-typing-indicator">
                <span class="live-chat-typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span class="live-chat-typing-text">typing...</span>
            </div>
        </div>

        <!-- Input -->
        <form @submit.prevent="sendMessage" class="live-chat-form">
            <div class="live-chat-input-wrapper">
                <textarea
                    v-model="newMessage"
                    @input="handleTyping"
                    @keydown.enter.exact.prevent="sendMessage"
                    ref="inputElement"
                    class="live-chat-input"
                    placeholder="Type a message..."
                    rows="1"
                ></textarea>
                <button
                    type="submit"
                    :disabled="!newMessage.trim() || isSending"
                    class="live-chat-send-button"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from "vue";

const props = defineProps({
    conversationId: {
        type: Number,
        required: true,
    },
    currentUserId: {
        type: Number,
        required: true,
    },
    otherUser: {
        type: Object,
        default: null,
    },
    initialMessages: {
        type: Array,
        default: () => [],
    },
    channelPrefix: {
        type: String,
        default: "chat",
    },
});

const emit = defineEmits(["message-sent", "message-received", "error"]);

// Reactive state
const messages = ref([...props.initialMessages]);
const newMessage = ref("");
const isTyping = ref(false);
const isOnline = ref(false);
const isSending = ref(false);

// Refs
const messagesContainer = ref(null);
const inputElement = ref(null);

// Echo channel
let channel = null;
let typingTimeout = null;

// Methods
const sendMessage = async () => {
    if (!newMessage.value.trim() || isSending.value) return;

    isSending.value = true;

    try {
        const response = await fetch(
            `/api/chat/conversations/${props.conversationId}/messages`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify({ message: newMessage.value }),
            }
        );

        if (!response.ok) throw new Error("Failed to send message");

        const data = await response.json();

        // Add message to list
        messages.value.push({
            ...data,
            isSent: true,
        });

        newMessage.value = "";
        await scrollToBottom();

        emit("message-sent", data);
    } catch (error) {
        console.error("Error sending message:", error);
        emit("error", error);
    } finally {
        isSending.value = false;
    }
};

const handleTyping = () => {
    clearTimeout(typingTimeout);

    // Broadcast typing
    if (channel) {
        channel.whisper("typing", {
            user_id: props.currentUserId,
            is_typing: true,
        });
    }

    // Stop typing after 1 second
    typingTimeout = setTimeout(() => {
        if (channel) {
            channel.whisper("typing", {
                user_id: props.currentUserId,
                is_typing: false,
            });
        }
    }, 1000);

    // Auto-resize
    nextTick(() => {
        if (inputElement.value) {
            inputElement.value.style.height = "auto";
            inputElement.value.style.height =
                inputElement.value.scrollHeight + "px";
        }
    });
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop =
            messagesContainer.value.scrollHeight;
    }
};

const formatTime = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) return "just now";
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;

    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
};

const subscribeToChannel = () => {
    if (!window.Echo) {
        console.error("Laravel Echo is not initialized");
        return;
    }

    const channelName = `${props.channelPrefix}.${props.conversationId}`;
    channel = window.Echo.private(channelName);

    // Listen for new messages
    channel.listen(".MessageSent", (event) => {
        if (event.sender_id !== props.currentUserId) {
            messages.value.push({
                ...event,
                isSent: false,
            });
            scrollToBottom();
            emit("message-received", event);
        }
    });

    // Listen for typing
    channel.listenForWhisper("typing", (event) => {
        if (event.user_id !== props.currentUserId) {
            isTyping.value = event.is_typing;

            if (event.is_typing) {
                setTimeout(() => {
                    isTyping.value = false;
                }, 3000);
            }
        }
    });

    isOnline.value = true;
};

// Lifecycle
onMounted(() => {
    subscribeToChannel();
    scrollToBottom();
});

onBeforeUnmount(() => {
    if (channel) {
        window.Echo.leave(`${props.channelPrefix}.${props.conversationId}`);
    }
    clearTimeout(typingTimeout);
});

// Watch for new messages
watch(
    () => messages.value.length,
    () => {
        scrollToBottom();
    }
);
</script>

<style scoped>
/* Import the base styles */
@import "/css/vendor/live-chat/live-chat.css";

/* Additional Vue-specific styles */
.online-indicator {
    color: #10b981;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}
</style>
```

## Usage in Blade

```blade
<div id="app">
    <live-chat
        :conversation-id="{{ $conversation->id }}"
        :current-user-id="{{ auth()->id() }}"
        :other-user="{{ $conversation->getOtherUser(auth()->user())->toJson() }}"
        :initial-messages="{{ $messages->toJson() }}"
    ></live-chat>
</div>

@vite(['resources/js/app.js', 'resources/css/app.css'])
```

## Register Component

In your `resources/js/app.js`:

```javascript
import { createApp } from "vue";
import LiveChat from "./components/LiveChat.vue";

const app = createApp({});
app.component("live-chat", LiveChat);
app.mount("#app");
```

## TypeScript Support

For TypeScript, create `resources/js/types/chat.ts`:

```typescript
export interface Message {
    id: number;
    conversation_id: number;
    sender_id: number;
    message: string;
    read_at: string | null;
    created_at: string;
    isSent?: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
}

export interface Conversation {
    id: number;
    user1_id: number;
    user2_id: number;
    last_message_at: string | null;
}
```

## Customization

You can customize the component by:

1. **Styling**: Override CSS variables or classes
2. **Events**: Listen to emitted events for custom behavior
3. **Props**: Pass additional configuration options
4. **Slots**: Add custom content (see advanced example)

## Advanced Features

For additional features like:

-   File attachments
-   Message reactions
-   Read receipts
-   Online presence

Check the full documentation at `/docs/frontend-integration.md`
