/**
 * Avatar Component
 *
 * User avatar with online status indicator
 */

import React from "react";

interface AvatarProps {
    name: string;
    src?: string;
    status?: "online" | "offline" | "away";
    size?: "sm" | "md" | "lg";
}

export const Avatar: React.FC<AvatarProps> = ({
    name,
    src,
    status,
    size = "md",
}) => {
    const initials = name
        .split(" ")
        .map((part) => part[0])
        .join("")
        .toUpperCase()
        .substring(0, 2);

    return (
        <div className={`live-chat__avatar live-chat__avatar--${size}`}>
            {src ? (
                <img src={src} alt={name} className="live-chat__avatar-image" />
            ) : (
                <div className="live-chat__avatar-initials">{initials}</div>
            )}

            {status && status !== "offline" && (
                <span
                    className={`live-chat__avatar-status live-chat__avatar-status--${status}`}
                    aria-label={`${status}`}
                />
            )}
        </div>
    );
};

Avatar.displayName = "Avatar";
