/**
 * useTyping Hook
 *
 * Manages typing indicators with debounce
 */

import { useCallback, useRef } from "react";
import { useConversations } from "../contexts/ConversationsContext";
import { useConnection } from "../contexts/ConnectionContext";

const TYPING_TIMEOUT = 3000; // Stop typing indicator after 3 seconds

export function useTyping() {
    const { activeConversationId } = useConversations();
    const { connected } = useConnection();
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const lastTypingRef = useRef<number>(0);

    const notifyTyping = useCallback(() => {
        if (!connected || !activeConversationId) return;

        const now = Date.now();

        // Only send if last typing notification was more than 2 seconds ago
        if (now - lastTypingRef.current < 2000) return;

        // TODO: Broadcast typing event via WebSocket
        // This will be implemented when WebSocket integration is complete
        console.log(`User is typing in conversation ${activeConversationId}`);

        lastTypingRef.current = now;

        // Clear existing timeout
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        // Set timeout to stop typing indicator
        typingTimeoutRef.current = setTimeout(() => {
            // TODO: Broadcast stop typing event
            console.log(
                `User stopped typing in conversation ${activeConversationId}`
            );
        }, TYPING_TIMEOUT);
    }, [connected, activeConversationId]);

    return { notifyTyping };
}
