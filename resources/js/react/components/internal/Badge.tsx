/**
 * Badge Component
 *
 * Unread message count badge
 */

import React from "react";

interface BadgeProps {
    count: number;
    max?: number;
}

export const Badge: React.FC<BadgeProps> = ({ count, max = 99 }) => {
    if (count <= 0) return null;

    const displayCount = count > max ? `${max}+` : count.toString();

    return (
        <span
            className="live-chat__badge"
            aria-label={`${count} unread messages`}
        >
            {displayCount}
        </span>
    );
};

Badge.displayName = "Badge";
