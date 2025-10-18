/**
 * MessageInput Component
 *
 * Compose area with:
 * - Auto-resizing textarea
 * - Emoji picker
 * - Typing indicator broadcast
 * - Send button with loading state
 * - Enter to send, Shift+Enter for newline
 */

import React, { useState, useRef, useEffect, useCallback } from "react";
import { EmojiPicker } from "./EmojiPicker";
import { useTyping } from "../../hooks/useTyping";

interface MessageInputProps {
    onSend: (content: string) => Promise<void>;
    disabled?: boolean;
}

export const MessageInput: React.FC<MessageInputProps> = ({
    onSend,
    disabled = false,
}) => {
    const [message, setMessage] = useState("");
    const [sending, setSending] = useState(false);
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const { notifyTyping } = useTyping();

    // Auto-resize textarea
    useEffect(() => {
        if (!textareaRef.current) return;

        textareaRef.current.style.height = "auto";
        textareaRef.current.style.height = `${textareaRef.current.scrollHeight}px`;
    }, [message]);

    // Handle input change and typing indicator
    const handleChange = useCallback(
        (e: React.ChangeEvent<HTMLTextAreaElement>) => {
            setMessage(e.target.value);
            notifyTyping();
        },
        [notifyTyping]
    );

    // Handle send
    const handleSend = useCallback(async () => {
        const trimmedMessage = message.trim();
        if (!trimmedMessage || sending || disabled) return;

        setSending(true);
        try {
            await onSend(trimmedMessage);
            setMessage("");

            // Reset textarea height
            if (textareaRef.current) {
                textareaRef.current.style.height = "auto";
            }
        } catch (error) {
            console.error("Failed to send message:", error);
        } finally {
            setSending(false);
        }
    }, [message, sending, disabled, onSend]);

    // Handle keyboard shortcuts
    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
            // Enter without Shift - send message
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        },
        [handleSend]
    );

    // Handle emoji selection
    const handleEmojiSelect = useCallback((emoji: string) => {
        setMessage((prev) => prev + emoji);
        setShowEmojiPicker(false);

        // Focus textarea
        if (textareaRef.current) {
            textareaRef.current.focus();
        }
    }, []);

    return (
        <div className="live-chat__input">
            <div className="live-chat__input-container">
                {/* Emoji Picker Button */}
                <button
                    type="button"
                    className="live-chat__button live-chat__button--icon"
                    onClick={() => setShowEmojiPicker(!showEmojiPicker)}
                    aria-label="Add emoji"
                    disabled={disabled}
                >
                    <svg
                        className="live-chat__icon"
                        width="20"
                        height="20"
                        viewBox="0 0 20 20"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <circle cx="10" cy="10" r="8" />
                        <path d="M7 12s1.5 2 3 2 3-2 3-2" />
                        <circle cx="7.5" cy="8.5" r="0.5" fill="currentColor" />
                        <circle
                            cx="12.5"
                            cy="8.5"
                            r="0.5"
                            fill="currentColor"
                        />
                    </svg>
                </button>

                {/* Emoji Picker */}
                {showEmojiPicker && (
                    <EmojiPicker
                        onSelect={handleEmojiSelect}
                        onClose={() => setShowEmojiPicker(false)}
                    />
                )}

                {/* Textarea */}
                <textarea
                    ref={textareaRef}
                    className="live-chat__input-textarea"
                    placeholder="Type a message..."
                    value={message}
                    onChange={handleChange}
                    onKeyDown={handleKeyDown}
                    disabled={disabled || sending}
                    rows={1}
                    aria-label="Message input"
                />

                {/* Send Button */}
                <button
                    type="button"
                    className="live-chat__button live-chat__button--primary live-chat__button--icon"
                    onClick={handleSend}
                    disabled={!message.trim() || disabled || sending}
                    aria-label="Send message"
                >
                    {sending ? (
                        <svg
                            className="live-chat__icon live-chat__icon--spin"
                            width="20"
                            height="20"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                        >
                            <circle
                                cx="10"
                                cy="10"
                                r="8"
                                strokeDasharray="12 8"
                            />
                        </svg>
                    ) : (
                        <svg
                            className="live-chat__icon"
                            width="20"
                            height="20"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M18 2L9 11M18 2l-7 16-4-7-7-4 16-7z" />
                        </svg>
                    )}
                </button>
            </div>
        </div>
    );
};

MessageInput.displayName = "MessageInput";
