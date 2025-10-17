/**
 * LoadingState Component
 *
 * Skeleton loaders for conversations and messages
 */

import React from "react";

interface LoadingStateProps {
    type: "conversations" | "messages";
    count?: number;
}

export const LoadingState: React.FC<LoadingStateProps> = ({
    type,
    count = 3,
}) => {
    if (type === "conversations") {
        return (
            <>
                {Array.from({ length: count }).map((_, index) => (
                    <div
                        key={`skeleton-conversation-${index}`}
                        className="live-chat__conversation-item live-chat__skeleton"
                    >
                        <div className="live-chat__skeleton-avatar" />
                        <div className="live-chat__conversation-item-content">
                            <div className="live-chat__skeleton-line live-chat__skeleton-line--title" />
                            <div className="live-chat__skeleton-line live-chat__skeleton-line--text" />
                        </div>
                    </div>
                ))}
            </>
        );
    }

    if (type === "messages") {
        return (
            <>
                {Array.from({ length: count }).map((_, index) => (
                    <div
                        key={`skeleton-message-${index}`}
                        className={`live-chat__message live-chat__skeleton ${
                            index % 2 === 0
                                ? "live-chat__message--sent"
                                : "live-chat__message--received"
                        }`}
                    >
                        <div className="live-chat__message-bubble">
                            <div className="live-chat__skeleton-line" />
                            <div className="live-chat__skeleton-line live-chat__skeleton-line--short" />
                        </div>
                    </div>
                ))}
            </>
        );
    }

    return null;
};

LoadingState.displayName = "LoadingState";
