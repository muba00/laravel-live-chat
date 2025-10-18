/**
 * EmptyState Component
 *
 * Display when there's no data to show
 */

import React from "react";

interface EmptyStateProps {
    message: string;
    action?: {
        label: string;
        onClick: () => void;
    };
}

export const EmptyState: React.FC<EmptyStateProps> = ({ message, action }) => {
    return (
        <div className="live-chat__empty-state">
            <svg
                className="live-chat__empty-state-icon"
                width="64"
                height="64"
                viewBox="0 0 64 64"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M21 15a2 2 0 0 1 2-2h18a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2H28l-7 7V35a2 2 0 0 1-2-2V15z" />
                <path d="M14 25a2 2 0 0 1-2 2v8l7-7h9" />
            </svg>
            <p className="live-chat__empty-state-message">{message}</p>
            {action && (
                <button
                    type="button"
                    className="live-chat__button live-chat__button--primary"
                    onClick={action.onClick}
                >
                    {action.label}
                </button>
            )}
        </div>
    );
};

EmptyState.displayName = "EmptyState";
