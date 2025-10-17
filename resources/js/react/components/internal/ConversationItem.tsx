/**
 * ConversationItem Component
 *
 * Single conversation row in the sidebar.
 * Shows participant name, avatar, last message preview, and unread badge.
 */

import React from "react";
import type { Conversation } from "../../types";
import { Avatar } from "./Avatar";
import { Badge } from "./Badge";
import { formatRelativeTime } from "../../lib/formatters";

interface ConversationItemProps {
    conversation: Conversation;
    isActive: boolean;
    onClick: () => void;
}

export const ConversationItem: React.FC<ConversationItemProps> = ({
    conversation,
    isActive,
    onClick,
}) => {
    // Get the other participant (assuming 1-to-1 chat)
    const otherParticipant = conversation.participants[0];

    const lastMessagePreview =
        conversation.lastMessage?.content || "No messages yet";
    const lastMessageTime = conversation.lastMessageAt
        ? formatRelativeTime(conversation.lastMessageAt)
        : "";

    return (
        <div
            className={`live-chat__conversation-item ${
                isActive ? "live-chat__conversation-item--active" : ""
            }`}
            onClick={onClick}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    onClick();
                }
            }}
            aria-current={isActive ? "true" : "false"}
        >
            <Avatar
                name={otherParticipant?.name || "Unknown"}
                src={otherParticipant?.avatar}
                status={otherParticipant?.status}
                size="md"
            />

            <div className="live-chat__conversation-item-content">
                <div className="live-chat__conversation-item-header">
                    <span className="live-chat__conversation-item-name">
                        {otherParticipant?.name || "Unknown User"}
                    </span>
                    {lastMessageTime && (
                        <span className="live-chat__conversation-item-time">
                            {lastMessageTime}
                        </span>
                    )}
                </div>

                <div className="live-chat__conversation-item-footer">
                    <p className="live-chat__conversation-item-preview">
                        {lastMessagePreview}
                    </p>
                    {conversation.unreadCount > 0 && (
                        <Badge count={conversation.unreadCount} />
                    )}
                </div>
            </div>
        </div>
    );
};

ConversationItem.displayName = "ConversationItem";
