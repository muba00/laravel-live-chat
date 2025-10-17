/**
 * MessageItem Component
 *
 * Single message bubble with:
 * - Avatar (optional)
 * - Message content (with link detection)
 * - Timestamp (optional)
 * - Read receipt
 * - Status indicator (sending/sent/failed)
 */

import React, { useMemo } from "react";
import type { Message } from "../../types";
import { Avatar } from "./Avatar";
import { formatMessageTime, linkify, sanitizeHTML } from "../../lib/formatters";

interface MessageItemProps {
    message: Message;
    showAvatar?: boolean;
    showTimestamp?: boolean;
}

export const MessageItem: React.FC<MessageItemProps> = ({
    message,
    showAvatar = true,
    showTimestamp = true,
}) => {
    // TODO: Get current user ID from context
    const currentUserId = 1; // This should come from context
    const isSent = message.senderId === currentUserId;

    // Linkify and sanitize content
    const content = useMemo(() => {
        const sanitized = sanitizeHTML(message.content);
        return linkify(sanitized);
    }, [message.content]);

    return (
        <div
            className={`live-chat__message ${
                isSent
                    ? "live-chat__message--sent"
                    : "live-chat__message--received"
            }`}
        >
            {/* Avatar (for received messages) */}
            {!isSent && showAvatar && message.sender && (
                <Avatar
                    name={message.sender.name}
                    src={message.sender.avatar}
                    size="sm"
                />
            )}

            <div className="live-chat__message-content">
                {/* Message Bubble */}
                <div
                    className={`live-chat__message-bubble ${
                        message.status
                            ? `live-chat__message-bubble--${message.status}`
                            : ""
                    }`}
                >
                    <div
                        className="live-chat__message-text"
                        dangerouslySetInnerHTML={{ __html: content }}
                    />

                    {/* Timestamp */}
                    {showTimestamp && (
                        <time
                            className="live-chat__message-time"
                            dateTime={message.createdAt}
                        >
                            {formatMessageTime(message.createdAt)}
                        </time>
                    )}
                </div>

                {/* Status Indicators */}
                {isSent && (
                    <div className="live-chat__message-status">
                        {message.status === "sending" && (
                            <svg
                                className="live-chat__icon live-chat__icon--sending"
                                width="12"
                                height="12"
                                viewBox="0 0 12 12"
                                fill="currentColor"
                            >
                                <circle cx="6" cy="6" r="1">
                                    <animate
                                        attributeName="opacity"
                                        values="0;1;0"
                                        dur="1s"
                                        repeatCount="indefinite"
                                    />
                                </circle>
                            </svg>
                        )}
                        {message.status === "sent" && message.readAt && (
                            <svg
                                className="live-chat__icon live-chat__icon--read"
                                width="16"
                                height="12"
                                viewBox="0 0 16 12"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M1 6l3 3 4-4M6 6l3 3 6-6" />
                            </svg>
                        )}
                        {message.status === "sent" && !message.readAt && (
                            <svg
                                className="live-chat__icon live-chat__icon--sent"
                                width="12"
                                height="12"
                                viewBox="0 0 12 12"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M1 6l3 3 7-7" />
                            </svg>
                        )}
                        {message.status === "failed" && (
                            <svg
                                className="live-chat__icon live-chat__icon--failed"
                                width="12"
                                height="12"
                                viewBox="0 0 12 12"
                                fill="currentColor"
                            >
                                <circle cx="6" cy="6" r="5" />
                                <path
                                    d="M4 4l4 4M8 4l-4 4"
                                    stroke="white"
                                    strokeWidth="1.5"
                                />
                            </svg>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

MessageItem.displayName = "MessageItem";
