/**
 * ConversationList - Left Sidebar Component
 *
 * Displays all conversations with:
 * - Server-side search with debounce
 * - Pagination (load more on scroll)
 * - Real-time updates (new messages, unread counts)
 * - User avatars and last message preview
 */

import React, { useCallback, useRef, useEffect } from "react";
import { useConversations } from "../../contexts/ConversationsContext";
import { useUI } from "../../contexts/UIContext";
import { ConversationItem } from "./ConversationItem";
import { LoadingState } from "./LoadingState";
import { EmptyState } from "./EmptyState";
import { ErrorState } from "./ErrorState";
import { useDebounce } from "../../hooks/useDebounce";

export const ConversationList: React.FC = () => {
    const {
        conversations,
        activeConversationId,
        loading,
        error,
        hasMore,
        loadMore,
        setActiveConversation,
        refresh,
    } = useConversations();

    const {
        sidebarOpen,
        newConversationModalOpen,
        searchQuery,
        isMobile,
        setSidebar,
        toggleNewConversationModal,
        setSearchQuery,
    } = useUI();

    const scrollRef = useRef<HTMLDivElement>(null);
    const debouncedSearchQuery = useDebounce(searchQuery, 300);

    // Handle scroll for pagination
    const handleScroll = useCallback(() => {
        if (!scrollRef.current || loading || !hasMore) return;

        const { scrollTop, scrollHeight, clientHeight } = scrollRef.current;
        const scrollThreshold = 100; // Load more when 100px from bottom

        if (scrollHeight - scrollTop - clientHeight < scrollThreshold) {
            loadMore();
        }
    }, [loading, hasMore, loadMore]);

    // Effect to handle search query changes
    useEffect(() => {
        if (debouncedSearchQuery !== undefined) {
            refresh();
        }
    }, [debouncedSearchQuery, refresh]);

    // Close sidebar on mobile when conversation is selected
    const handleConversationClick = useCallback(
        (conversationId: number) => {
            setActiveConversation(conversationId);
            if (isMobile) {
                setSidebar(false);
            }
        },
        [setActiveConversation, isMobile, setSidebar]
    );

    return (
        <aside
            className={`live-chat__sidebar ${
                sidebarOpen ? "live-chat__sidebar--open" : ""
            }`}
            aria-label="Conversations"
        >
            {/* Header */}
            <div className="live-chat__sidebar-header">
                <h2 className="live-chat__sidebar-title">Messages</h2>
                <button
                    type="button"
                    className="live-chat__button live-chat__button--icon"
                    onClick={toggleNewConversationModal}
                    aria-label="Start new conversation"
                    title="New conversation"
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
                        <path d="M10 5v10M5 10h10" />
                    </svg>
                </button>
            </div>

            {/* Search */}
            <div className="live-chat__search">
                <input
                    type="search"
                    className="live-chat__search-input"
                    placeholder="Search conversations..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    aria-label="Search conversations"
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

            {/* Conversation List */}
            <div
                ref={scrollRef}
                className="live-chat__conversations"
                onScroll={handleScroll}
                role="list"
                aria-busy={loading}
            >
                {/* Error State */}
                {error && !loading && (
                    <ErrorState
                        message={
                            error.message || "Failed to load conversations"
                        }
                        onRetry={refresh}
                    />
                )}

                {/* Empty State */}
                {!error && !loading && conversations.length === 0 && (
                    <EmptyState
                        message={
                            searchQuery
                                ? "No conversations found"
                                : "No conversations yet"
                        }
                        action={
                            !searchQuery
                                ? {
                                      label: "Start a conversation",
                                      onClick: toggleNewConversationModal,
                                  }
                                : undefined
                        }
                    />
                )}

                {/* Conversation Items */}
                {conversations.map((conversation) => (
                    <ConversationItem
                        key={conversation.id}
                        conversation={conversation}
                        isActive={conversation.id === activeConversationId}
                        onClick={() => handleConversationClick(conversation.id)}
                    />
                ))}

                {/* Loading State */}
                {loading && <LoadingState type="conversations" count={3} />}

                {/* Load More Indicator */}
                {hasMore && !loading && (
                    <div className="live-chat__load-more">
                        <button
                            type="button"
                            className="live-chat__button live-chat__button--text"
                            onClick={loadMore}
                        >
                            Load more
                        </button>
                    </div>
                )}
            </div>
        </aside>
    );
};

ConversationList.displayName = "ConversationList";
