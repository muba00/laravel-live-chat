/**
 * ConversationItem Component
 *
 * Single conversation row in the sidebar.
 * Shows participant name, avatar, last message preview, and unread badge.
 * Includes delete button on hover.
 */

import React, { useState, useCallback } from "react";
import type { Conversation } from "../../types";
import { Avatar } from "./Avatar";
import { Badge } from "./Badge";
import { formatRelativeTime } from "../../lib/formatters";
import { useUI } from "../../contexts/UIContext";

interface ConversationItemProps {
    conversation: Conversation;
    isActive: boolean;
    onClick: () => void;
    onDelete?: (conversationId: number) => void;
}

export const ConversationItem: React.FC<ConversationItemProps> = ({
    conversation,
    isActive,
    onClick,
    onDelete,
}) => {
    const [showDelete, setShowDelete] = useState(false);
    const { showConfirm } = useUI();
    // Get the other participant (assuming 1-to-1 chat)
    const otherParticipant = conversation.participants[0];

    const lastMessagePreview =
        conversation.lastMessage?.content || "No messages yet";
    const lastMessageTime = conversation.lastMessageAt
        ? formatRelativeTime(conversation.lastMessageAt)
        : "";

    // Handle delete with confirmation
    const handleDelete = useCallback(
        async (e: React.MouseEvent) => {
            e.stopPropagation(); // Prevent conversation selection

            const confirmed = await showConfirm(
                "Delete Conversation",
                `Are you sure you want to delete this conversation with ${
                    otherParticipant?.name || "this user"
                }? This action cannot be undone.`
            );

            if (confirmed && onDelete) {
                onDelete(conversation.id);
            }
        },
        [conversation.id, onDelete, otherParticipant?.name, showConfirm]
    );

    return (
        <div
            className={`live-chat__conversation-item ${
                isActive ? "live-chat__conversation-item--active" : ""
            }`}
            onClick={onClick}
            onMouseEnter={() => setShowDelete(true)}
            onMouseLeave={() => setShowDelete(false)}
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

            {/* Delete Button */}
            {showDelete && onDelete && (
                <button
                    type="button"
                    className="live-chat__conversation-item-delete"
                    onClick={handleDelete}
                    aria-label="Delete conversation"
                    title="Delete conversation"
                >
                    <svg
                        className="live-chat__icon"
                        width="18"
                        height="18"
                        viewBox="0 0 18 18"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <path d="M2 4h14M6 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1M14 4v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4h10z" />
                        <path d="M7 8v6M11 8v6" />
                    </svg>
                </button>
            )}
        </div>
    );
};

ConversationItem.displayName = "ConversationItem";
