# React Chat Component Example

This example shows how to integrate Laravel Live Chat with React using hooks.

## Installation

```bash
npm install --save-dev react react-dom laravel-echo pusher-js
```

## Setup Laravel Echo

Create `resources/js/echo.js`:

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

export default window.Echo;
```

## Chat Component

Create `resources/js/components/LiveChat.jsx`:

```jsx
import React, { useState, useEffect, useRef, useCallback } from "react";
import Echo from "../echo";

const LiveChat = ({
    conversationId,
    currentUserId,
    otherUser = null,
    initialMessages = [],
    channelPrefix = "chat",
    onMessageSent = null,
    onMessageReceived = null,
    onError = null,
}) => {
    // State
    const [messages, setMessages] = useState(initialMessages);
    const [newMessage, setNewMessage] = useState("");
    const [isTyping, setIsTyping] = useState(false);
    const [isSending, setIsSending] = useState(false);

    // Refs
    const messagesContainerRef = useRef(null);
    const inputRef = useRef(null);
    const channelRef = useRef(null);
    const typingTimeoutRef = useRef(null);

    // Scroll to bottom
    const scrollToBottom = useCallback(() => {
        if (messagesContainerRef.current) {
            messagesContainerRef.current.scrollTop =
                messagesContainerRef.current.scrollHeight;
        }
    }, []);

    // Format timestamp
    const formatTime = (timestamp) => {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return "just now";
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;

        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    // Send message
    const sendMessage = async (e) => {
        e.preventDefault();

        if (!newMessage.trim() || isSending) return;

        setIsSending(true);

        try {
            const response = await fetch(
                `/api/chat/conversations/${conversationId}/messages`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({ message: newMessage }),
                }
            );

            if (!response.ok) throw new Error("Failed to send message");

            const data = await response.json();

            setMessages((prev) => [...prev, { ...data, isSent: true }]);
            setNewMessage("");

            if (onMessageSent) onMessageSent(data);
        } catch (error) {
            console.error("Error sending message:", error);
            if (onError) onError(error);
        } finally {
            setIsSending(false);
        }
    };

    // Handle typing
    const handleTyping = (e) => {
        setNewMessage(e.target.value);

        clearTimeout(typingTimeoutRef.current);

        // Broadcast typing start
        if (channelRef.current) {
            channelRef.current.whisper("typing", {
                user_id: currentUserId,
                is_typing: true,
            });
        }

        // Broadcast typing stop after 1 second
        typingTimeoutRef.current = setTimeout(() => {
            if (channelRef.current) {
                channelRef.current.whisper("typing", {
                    user_id: currentUserId,
                    is_typing: false,
                });
            }
        }, 1000);
    };

    // Auto-resize textarea
    const autoResizeTextarea = () => {
        if (inputRef.current) {
            inputRef.current.style.height = "auto";
            inputRef.current.style.height =
                inputRef.current.scrollHeight + "px";
        }
    };

    // Subscribe to channel
    useEffect(() => {
        if (!Echo) {
            console.error("Laravel Echo is not initialized");
            return;
        }

        const channelName = `${channelPrefix}.${conversationId}`;
        channelRef.current = Echo.private(channelName);

        // Listen for new messages
        channelRef.current.listen(".MessageSent", (event) => {
            if (event.sender_id !== currentUserId) {
                setMessages((prev) => [...prev, { ...event, isSent: false }]);
                if (onMessageReceived) onMessageReceived(event);
            }
        });

        // Listen for typing
        channelRef.current.listenForWhisper("typing", (event) => {
            if (event.user_id !== currentUserId) {
                setIsTyping(event.is_typing);

                if (event.is_typing) {
                    setTimeout(() => setIsTyping(false), 3000);
                }
            }
        });

        // Cleanup
        return () => {
            if (channelRef.current) {
                Echo.leave(channelName);
            }
            clearTimeout(typingTimeoutRef.current);
        };
    }, [conversationId, currentUserId, channelPrefix, onMessageReceived]);

    // Scroll to bottom when messages change
    useEffect(() => {
        scrollToBottom();
    }, [messages, scrollToBottom]);

    // Auto-resize textarea on input
    useEffect(() => {
        autoResizeTextarea();
    }, [newMessage]);

    return (
        <div className="live-chat-window">
            {/* Header */}
            <div className="live-chat-header">
                <h3 className="live-chat-title">{otherUser?.name || "Chat"}</h3>
            </div>

            {/* Messages */}
            <div ref={messagesContainerRef} className="live-chat-messages">
                {messages.map((message) => (
                    <div
                        key={message.id}
                        className={`live-chat-message ${
                            message.isSent
                                ? "live-chat-message-sent"
                                : "live-chat-message-received"
                        }`}
                    >
                        <div className="live-chat-message-content">
                            <div className="live-chat-message-text">
                                {message.message}
                            </div>
                            <div className="live-chat-message-meta">
                                <span className="live-chat-message-time">
                                    {formatTime(message.created_at)}
                                </span>
                                {message.isSent && message.read_at && (
                                    <span className="live-chat-message-status">
                                        ✓✓
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                ))}

                {/* Typing Indicator */}
                {isTyping && (
                    <div className="live-chat-typing-indicator">
                        <span className="live-chat-typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                        <span className="live-chat-typing-text">typing...</span>
                    </div>
                )}
            </div>

            {/* Input */}
            <form onSubmit={sendMessage} className="live-chat-form">
                <div className="live-chat-input-wrapper">
                    <textarea
                        ref={inputRef}
                        value={newMessage}
                        onChange={handleTyping}
                        onKeyPress={(e) => {
                            if (e.key === "Enter" && !e.shiftKey) {
                                e.preventDefault();
                                sendMessage(e);
                            }
                        }}
                        className="live-chat-input"
                        placeholder="Type a message..."
                        rows="1"
                        disabled={isSending}
                    />
                    <button
                        type="submit"
                        disabled={!newMessage.trim() || isSending}
                        className="live-chat-send-button"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                        >
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    );
};

export default LiveChat;
```

## Usage with Inertia.js

If you're using Inertia.js, create a page component:

```jsx
// resources/js/Pages/Chat/Show.jsx
import React from "react";
import LiveChat from "../../components/LiveChat";

const Show = ({ conversation, messages, auth }) => {
    const otherUser =
        conversation.user1_id === auth.user.id
            ? conversation.user2
            : conversation.user1;

    return (
        <div className="container mx-auto py-8">
            <LiveChat
                conversationId={conversation.id}
                currentUserId={auth.user.id}
                otherUser={otherUser}
                initialMessages={messages}
                onMessageSent={(message) =>
                    console.log("Message sent:", message)
                }
                onMessageReceived={(message) =>
                    console.log("Message received:", message)
                }
                onError={(error) => console.error("Error:", error)}
            />
        </div>
    );
};

export default Show;
```

## Usage in Blade

```blade
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.jsx', 'resources/css/app.css'])
</head>
<body>
    <div id="app"></div>

    <script>
        window.chatData = {
            conversationId: {{ $conversation->id }},
            currentUserId: {{ auth()->id() }},
            otherUser: @json($conversation->getOtherUser(auth()->user())),
            initialMessages: @json($messages)
        };
    </script>
</body>
</html>
```

Register component in `resources/js/app.jsx`:

```jsx
import React from "react";
import { createRoot } from "react-dom/client";
import LiveChat from "./components/LiveChat";
import "./echo"; // Initialize Echo

const container = document.getElementById("app");
const root = createRoot(container);

root.render(
    <React.StrictMode>
        <LiveChat
            conversationId={window.chatData.conversationId}
            currentUserId={window.chatData.currentUserId}
            otherUser={window.chatData.otherUser}
            initialMessages={window.chatData.initialMessages}
        />
    </React.StrictMode>
);
```

## TypeScript Support

Create `resources/js/types/chat.ts`:

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

export interface LiveChatProps {
    conversationId: number;
    currentUserId: number;
    otherUser?: User | null;
    initialMessages?: Message[];
    channelPrefix?: string;
    onMessageSent?: (message: Message) => void;
    onMessageReceived?: (message: Message) => void;
    onError?: (error: Error) => void;
}
```

Then update component to TypeScript:

```tsx
// resources/js/components/LiveChat.tsx
import React, { FC } from "react";
import { LiveChatProps, Message } from "../types/chat";

const LiveChat: FC<LiveChatProps> = ({
    conversationId,
    currentUserId,
    otherUser = null,
    initialMessages = [],
    channelPrefix = "chat",
    onMessageSent = null,
    onMessageReceived = null,
    onError = null,
}) => {
    // Component implementation...
};

export default LiveChat;
```

## Custom Hooks

Create reusable hooks:

```jsx
// resources/js/hooks/useLiveChat.js
import { useState, useEffect, useRef } from "react";

export const useLiveChat = (
    conversationId,
    currentUserId,
    channelPrefix = "chat"
) => {
    const [messages, setMessages] = useState([]);
    const [isTyping, setIsTyping] = useState(false);
    const channelRef = useRef(null);

    useEffect(() => {
        if (!window.Echo) return;

        const channelName = `${channelPrefix}.${conversationId}`;
        channelRef.current = window.Echo.private(channelName);

        channelRef.current.listen(".MessageSent", (event) => {
            if (event.sender_id !== currentUserId) {
                setMessages((prev) => [...prev, { ...event, isSent: false }]);
            }
        });

        channelRef.current.listenForWhisper("typing", (event) => {
            if (event.user_id !== currentUserId) {
                setIsTyping(event.is_typing);
                if (event.is_typing) {
                    setTimeout(() => setIsTyping(false), 3000);
                }
            }
        });

        return () => {
            window.Echo.leave(channelName);
        };
    }, [conversationId, currentUserId, channelPrefix]);

    const sendMessage = async (message) => {
        const response = await fetch(
            `/api/chat/conversations/${conversationId}/messages`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify({ message }),
            }
        );

        if (!response.ok) throw new Error("Failed to send message");

        const data = await response.json();
        setMessages((prev) => [...prev, { ...data, isSent: true }]);
        return data;
    };

    const broadcastTyping = (isTyping) => {
        if (channelRef.current) {
            channelRef.current.whisper("typing", {
                user_id: currentUserId,
                is_typing: isTyping,
            });
        }
    };

    return {
        messages,
        isTyping,
        sendMessage,
        broadcastTyping,
        setMessages,
    };
};
```

## Styling

Import the base CSS in your app:

```css
/* resources/css/app.css */
@import "/css/vendor/live-chat/live-chat.css";

/* Custom overrides */
.live-chat-window {
    max-width: 800px;
    margin: 0 auto;
}
```

## Next Steps

-   Add file upload support
-   Implement message reactions
-   Add read receipts UI
-   Build conversation list component
