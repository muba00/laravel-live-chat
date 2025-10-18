/**
 * MessageList Component
 *
 * Renders messages with:
 * - Date separators
 * - Message grouping by sender
 * - Optimistic UI for sending messages
 */

import React from "react";
import type { Message } from "../../types";
import { MessageGroup } from "./MessageGroup";
import { formatDateSeparator } from "../../lib/formatters";

interface MessageListProps {
    messages: Message[];
}

export const MessageList: React.FC<MessageListProps> = ({ messages }) => {
    // Group messages by date and sender
    const groupedMessages: Array<{
        date: string;
        groups: Array<{
            senderId: number;
            messages: Message[];
        }>;
    }> = [];

    let currentDate: string | null = null;
    let currentGroup: Message[] = [];
    let currentSenderId: number | null = null;

    messages.forEach((message, index) => {
        const messageDate = new Date(message.createdAt).toDateString();

        // New date - add date separator
        if (messageDate !== currentDate) {
            // Finish current group if exists
            if (currentGroup.length > 0 && currentSenderId !== null) {
                const lastDateGroup =
                    groupedMessages[groupedMessages.length - 1];
                if (lastDateGroup) {
                    lastDateGroup.groups.push({
                        senderId: currentSenderId,
                        messages: currentGroup,
                    });
                }
            }

            // Start new date group
            currentDate = messageDate;
            groupedMessages.push({
                date: message.createdAt,
                groups: [],
            });
            currentGroup = [message];
            currentSenderId = message.senderId;
        }
        // Same date, but different sender - new group
        else if (message.senderId !== currentSenderId) {
            // Finish current group
            if (currentGroup.length > 0 && currentSenderId !== null) {
                const lastDateGroup =
                    groupedMessages[groupedMessages.length - 1];
                if (lastDateGroup) {
                    lastDateGroup.groups.push({
                        senderId: currentSenderId,
                        messages: currentGroup,
                    });
                }
            }

            // Start new sender group
            currentGroup = [message];
            currentSenderId = message.senderId;
        }
        // Same sender - add to current group
        else {
            currentGroup.push(message);
        }

        // Last message - finish the group
        if (index === messages.length - 1 && currentSenderId !== null) {
            const lastDateGroup = groupedMessages[groupedMessages.length - 1];
            if (lastDateGroup) {
                lastDateGroup.groups.push({
                    senderId: currentSenderId,
                    messages: currentGroup,
                });
            }
        }
    });

    return (
        <div className="live-chat__message-list">
            {groupedMessages.map((dateGroup, dateIndex) => (
                <div key={`date-${dateIndex}`}>
                    {/* Date Separator */}
                    <div className="live-chat__date-separator">
                        <span>{formatDateSeparator(dateGroup.date)}</span>
                    </div>

                    {/* Message Groups */}
                    {dateGroup.groups.map((group, groupIndex) => (
                        <MessageGroup
                            key={`group-${dateIndex}-${groupIndex}`}
                            messages={group.messages}
                            senderId={group.senderId}
                        />
                    ))}
                </div>
            ))}
        </div>
    );
};

MessageList.displayName = "MessageList";
