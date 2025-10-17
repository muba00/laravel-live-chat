/**
 * MessageGroup Component
 *
 * Groups consecutive messages from the same sender
 */

import React from "react";
import type { Message } from "../../types";
import { MessageItem } from "./MessageItem";

interface MessageGroupProps {
    messages: Message[];
    senderId: number;
}

export const MessageGroup: React.FC<MessageGroupProps> = ({
    messages,
    senderId,
}) => {
    if (messages.length === 0) return null;

    return (
        <div className="live-chat__message-group">
            {messages.map((message, index) => (
                <MessageItem
                    key={message.id || message.tempId || index}
                    message={message}
                    showAvatar={index === messages.length - 1}
                    showTimestamp={index === messages.length - 1}
                />
            ))}
        </div>
    );
};

MessageGroup.displayName = "MessageGroup";
