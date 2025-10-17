/**
 * UserSearch Component
 *
 * Server-side user search for starting new conversations
 */

import React, { useState, useCallback, useEffect } from "react";
import type { User } from "../../types";
import { Avatar } from "./Avatar";
import { useDebounce } from "../../hooks/useDebounce";

interface UserSearchProps {
    onSelectUser: (userId: number) => void;
    disabled?: boolean;
}

export const UserSearch: React.FC<UserSearchProps> = ({
    onSelectUser,
    disabled = false,
}) => {
    const [query, setQuery] = useState("");
    const [users, setUsers] = useState<User[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const debouncedQuery = useDebounce(query, 300);

    // Search users
    useEffect(() => {
        if (!debouncedQuery.trim()) {
            setUsers([]);
            return;
        }

        const searchUsers = async () => {
            setLoading(true);
            setError(null);

            try {
                // TODO: Implement actual API call
                // For now, mock data
                await new Promise((resolve) => setTimeout(resolve, 500));

                const mockUsers: User[] = [
                    {
                        id: 2,
                        name: `User matching "${debouncedQuery}"`,
                        email: "user@example.com",
                        status: "online",
                    },
                ];

                setUsers(mockUsers);
            } catch (err) {
                setError("Failed to search users");
                setUsers([]);
            } finally {
                setLoading(false);
            }
        };

        searchUsers();
    }, [debouncedQuery]);

    const handleSelectUser = useCallback(
        (userId: number) => {
            if (disabled) return;
            onSelectUser(userId);
        },
        [disabled, onSelectUser]
    );

    return (
        <div className="live-chat__user-search">
            {/* Search Input */}
            <div className="live-chat__search">
                <input
                    type="search"
                    className="live-chat__search-input"
                    placeholder="Search users..."
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    disabled={disabled}
                    autoFocus
                    aria-label="Search users"
                />
                <svg
                    className="live-chat__search-icon"
                    width="18"
                    height="18"
                    viewBox="0 0 18 18"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <circle cx="8" cy="8" r="6" />
                    <path d="m13 13 4 4" />
                </svg>
            </div>

            {/* Results */}
            <div className="live-chat__user-search-results">
                {loading && (
                    <div className="live-chat__user-search-loading">
                        Searching...
                    </div>
                )}

                {error && (
                    <div className="live-chat__user-search-error">{error}</div>
                )}

                {!loading && !error && users.length === 0 && debouncedQuery && (
                    <div className="live-chat__user-search-empty">
                        No users found
                    </div>
                )}

                {!loading && !error && users.length > 0 && (
                    <div className="live-chat__user-list">
                        {users.map((user) => (
                            <button
                                key={user.id}
                                type="button"
                                className="live-chat__user-item"
                                onClick={() => handleSelectUser(user.id)}
                                disabled={disabled}
                            >
                                <Avatar
                                    name={user.name}
                                    src={user.avatar}
                                    status={user.status}
                                    size="md"
                                />
                                <div className="live-chat__user-item-info">
                                    <div className="live-chat__user-item-name">
                                        {user.name}
                                    </div>
                                    {user.email && (
                                        <div className="live-chat__user-item-email">
                                            {user.email}
                                        </div>
                                    )}
                                </div>
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

UserSearch.displayName = "UserSearch";
