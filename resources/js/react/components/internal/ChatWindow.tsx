/**
 * ChatWindow Component
 *
 * Main chat area displaying:
 * - Message list with auto-scroll
 * - Date separators
 * - Typing indicators
 * - Load older messages on scroll to top
 */

import React, { useRef, useEffect, useCallback } from "react";
import { useMessages } from "../../contexts/MessagesContext";
import { useConversations } from "../../contexts/ConversationsContext";
import { MessageList } from "./MessageList";
import { MessageInput } from "./MessageInput";
import { LoadingState } from "./LoadingState";
import { EmptyState } from "./EmptyState";
import { ErrorState } from "./ErrorState";
import type { Conversation } from "../../types";

export const ChatWindow: React.FC = () => {
    const {
        messages: rawMessages,
        loading,
        error,
        hasMore,
        loadMore,
        sendMessage,
        refresh,
    } = useMessages();

    const {
        state: { activeConversationId, conversations },
    } = useConversations();

    // Ensure messages is always an array to prevent undefined errors
    const messages = rawMessages || [];

    const scrollRef = useRef<HTMLDivElement>(null);
    const prevScrollHeight = useRef<number>(0);

    // Get active conversation
    const activeConversation = conversations?.find(
        (c: Conversation) => c.id === activeConversationId
    );

    const otherParticipant = activeConversation?.participants[0];

    // Auto-scroll to bottom on new messages
    useEffect(() => {
        if (!scrollRef.current) return;

        const { scrollHeight, scrollTop, clientHeight } = scrollRef.current;
        const isNearBottom = scrollHeight - scrollTop - clientHeight < 100;

        // Scroll to bottom if user is near bottom or on initial load
        if (isNearBottom || messages.length === 0) {
            scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
        }
    }, [messages]);

    // Handle scroll for loading older messages
    const handleScroll = useCallback(() => {
        if (!scrollRef.current || loading || !hasMore) return;

        const { scrollTop } = scrollRef.current;
        const scrollThreshold = 50; // Load more when 50px from top

        if (scrollTop < scrollThreshold) {
            // Save current scroll position
            prevScrollHeight.current = scrollRef.current.scrollHeight;
            loadMore();
        }
    }, [loading, hasMore, loadMore]);

    // Maintain scroll position after loading older messages
    useEffect(() => {
        if (!scrollRef.current || !prevScrollHeight.current) return;

        const newScrollHeight = scrollRef.current.scrollHeight;
        const scrollDiff = newScrollHeight - prevScrollHeight.current;

        if (scrollDiff > 0) {
            scrollRef.current.scrollTop += scrollDiff;
        }

        prevScrollHeight.current = 0;
    }, [messages.length]);

    // Handle message send
    const handleSend = useCallback(
        async (content: string) => {
            await sendMessage(content);

            // Scroll to bottom after sending
            if (scrollRef.current) {
                setTimeout(() => {
                    if (scrollRef.current) {
                        scrollRef.current.scrollTop =
                            scrollRef.current.scrollHeight;
                    }
                }, 100);
            }
        },
        [sendMessage]
    );

    // No conversation selected
    if (!activeConversationId) {
        return (
            <div className="live-chat__chat-window">
                <EmptyState message="Select a conversation to start chatting" />
            </div>
        );
    }

    return (
        <div className="live-chat__chat-window">
            {/* Header */}
            <div className="live-chat__chat-header">
                <div className="live-chat__chat-header-info">
                    <h3 className="live-chat__chat-header-name">
                        {otherParticipant?.name || "Unknown User"}
                    </h3>
                    {otherParticipant?.status && (
                        <span className="live-chat__chat-header-status">
                            {otherParticipant.status}
                        </span>
                    )}
                </div>
            </div>

            {/* Messages */}
            <div
                ref={scrollRef}
                className="live-chat__messages"
                onScroll={handleScroll}
                role="log"
                aria-live="polite"
                aria-atomic="false"
            >
                {/* Loading older messages */}
                {loading && hasMore && (
                    <div className="live-chat__load-more-messages">
                        <LoadingState type="messages" count={2} />
                    </div>
                )}

                {/* Error State */}
                {error && !loading && (
                    <ErrorState
                        message={error.message || "Failed to load messages"}
                        onRetry={refresh}
                    />
                )}

                {/* Messages List */}
                {!error && messages.length > 0 && (
                    <MessageList messages={messages} />
                )}

                {/* Empty State */}
                {!error && !loading && messages.length === 0 && (
                    <div className="live-chat__messages-empty">
                        <EmptyState message="No messages yet. Start the conversation!" />
                    </div>
                )}
            </div>

            {/* Input */}
            <MessageInput onSend={handleSend} disabled={loading} />
        </div>
    );
};

ChatWindow.displayName = "ChatWindow";
