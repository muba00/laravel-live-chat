/**
 * LiveChat - Main Component
 *
 * Single component with all features for 1-to-1 real-time chat.
 * Wraps all contexts and renders the complete chat UI.
 */

// Import styles so they're always included with the component
import "../styles/index.css";

import React, { useEffect } from "react";
import { ConversationsProvider } from "../contexts/ConversationsContext";
import { MessagesProvider } from "../contexts/MessagesContext";
import { ConnectionProvider } from "../contexts/ConnectionContext";
import { UIProvider } from "../contexts/UIContext";
import { ConversationList } from "./internal/ConversationList";
import { ChatWindow } from "./internal/ChatWindow";
import { NewConversation } from "./internal/NewConversation";
import { Toast } from "./internal/Toast";
import { ConfirmDialog } from "./internal/ConfirmDialog";
import type { LiveChatProps } from "../types";

/**
 * LiveChat Component
 *
 * @example
 * ```tsx
 * import { LiveChat } from '@muba00/laravel-live-chat-react';
 * import '@muba00/laravel-live-chat-react/styles';
 *
 * function App() {
 *   return <LiveChat userId={1} />;
 * }
 * ```
 */
export const LiveChat: React.FC<LiveChatProps> = ({
    userId,
    apiUrl = "/api/chat",
    wsHost,
    wsPort = 6001,
    wsKey,
    theme = "light",
    height = "600px",
    width = "100%",
    className = "",
}) => {
    // Set theme on mount
    useEffect(() => {
        document.documentElement.setAttribute("data-live-chat-theme", theme);
    }, [theme]);

    return (
        <div
            className={`live-chat ${className}`}
            style={
                {
                    height,
                    width,
                    "--live-chat-height": height,
                    "--live-chat-width": width,
                } as React.CSSProperties
            }
            data-theme={theme}
        >
            <ConnectionProvider wsHost={wsHost} wsPort={wsPort} wsKey={wsKey}>
                <UIProvider>
                    <ConversationsProvider userId={userId} apiUrl={apiUrl}>
                        <MessagesProvider userId={userId} apiUrl={apiUrl}>
                            <div className="live-chat__container">
                                <ConversationList />
                                <ChatWindow />
                                <NewConversation />
                                <Toast />
                                <ConfirmDialog />
                            </div>
                        </MessagesProvider>
                    </ConversationsProvider>
                </UIProvider>
            </ConnectionProvider>
        </div>
    );
};

LiveChat.displayName = "LiveChat";
