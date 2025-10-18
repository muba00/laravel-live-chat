/**
 * ErrorState Component
 *
 * Display error messages with retry option
 */

import React from "react";

interface ErrorStateProps {
    message: string;
    onRetry?: () => void;
}

export const ErrorState: React.FC<ErrorStateProps> = ({ message, onRetry }) => {
    return (
        <div className="live-chat__error-state">
            <svg
                className="live-chat__error-state-icon"
                width="48"
                height="48"
                viewBox="0 0 48 48"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <circle cx="24" cy="24" r="20" />
                <path d="M24 16v8M24 32h.01" />
            </svg>
            <p className="live-chat__error-state-message">{message}</p>
            {onRetry && (
                <button
                    type="button"
                    className="live-chat__button live-chat__button--secondary"
                    onClick={onRetry}
                >
                    Try again
                </button>
            )}
        </div>
    );
};

ErrorState.displayName = "ErrorState";
