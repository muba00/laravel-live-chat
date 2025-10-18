/**
 * EmojiPicker Component
 *
 * Simple, lightweight emoji picker without external dependencies
 */

import React from "react";

interface EmojiPickerProps {
    onSelect: (emoji: string) => void;
    onClose: () => void;
}

const EMOJI_CATEGORIES = {
    Smileys: [
        "😀",
        "😃",
        "😄",
        "😁",
        "😅",
        "😂",
        "🤣",
        "😊",
        "😇",
        "🙂",
        "🙃",
        "😉",
        "😌",
        "😍",
        "🥰",
        "😘",
        "😗",
        "😙",
        "😚",
        "😋",
        "😛",
        "😝",
        "😜",
        "🤪",
        "🤨",
        "🧐",
        "🤓",
        "😎",
        "🤩",
        "🥳",
    ],
    Gestures: [
        "👍",
        "👎",
        "👌",
        "✌️",
        "🤞",
        "🤟",
        "🤘",
        "🤙",
        "👈",
        "👉",
        "👆",
        "👇",
        "☝️",
        "✋",
        "🤚",
        "🖐️",
        "🖖",
        "👋",
        "🤝",
        "🙏",
    ],
    Hearts: [
        "❤️",
        "🧡",
        "💛",
        "💚",
        "💙",
        "💜",
        "🖤",
        "🤍",
        "🤎",
        "💔",
        "❣️",
        "💕",
        "💞",
        "💓",
        "💗",
        "💖",
        "💘",
        "💝",
    ],
    Symbols: [
        "✅",
        "❌",
        "⭐",
        "🌟",
        "💯",
        "🔥",
        "✨",
        "💥",
        "💫",
        "🎉",
        "🎊",
        "🎈",
        "🎁",
        "🏆",
        "🥇",
        "🥈",
        "🥉",
    ],
};

export const EmojiPicker: React.FC<EmojiPickerProps> = ({
    onSelect,
    onClose,
}) => {
    return (
        <>
            {/* Backdrop */}
            <div
                className="live-chat__emoji-picker-backdrop"
                onClick={onClose}
                aria-hidden="true"
            />

            {/* Picker */}
            <div
                className="live-chat__emoji-picker"
                role="dialog"
                aria-label="Emoji picker"
            >
                {Object.entries(EMOJI_CATEGORIES).map(([category, emojis]) => (
                    <div key={category} className="live-chat__emoji-category">
                        <div className="live-chat__emoji-category-name">
                            {category}
                        </div>
                        <div className="live-chat__emoji-grid">
                            {emojis.map((emoji) => (
                                <button
                                    key={emoji}
                                    type="button"
                                    className="live-chat__emoji-button"
                                    onClick={() => onSelect(emoji)}
                                    aria-label={emoji}
                                >
                                    {emoji}
                                </button>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </>
    );
};

EmojiPicker.displayName = "EmojiPicker";
