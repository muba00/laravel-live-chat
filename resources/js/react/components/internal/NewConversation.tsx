/**
 * NewConversation Component
 *
 * Modal for starting a new conversation with user search
 */

import React, { useState, useCallback } from "react";
import { useUI } from "../../contexts/UIContext";
import { useConversations } from "../../contexts/ConversationsContext";
import { UserSearch } from "./UserSearch";

export const NewConversation: React.FC = () => {
    const { newConversationModalOpen, setNewConversationModal } = useUI();
    const { setActiveConversation } = useConversations();
    const [creating, setCreating] = useState(false);

    const handleSelectUser = useCallback(
        async (userId: number) => {
            setCreating(true);
            try {
                // TODO: Create conversation via API
                // For now, just set active conversation
                console.log("Creating conversation with user:", userId);

                // Close modal
                setNewConversationModal(false);
            } catch (error) {
                console.error("Failed to create conversation:", error);
            } finally {
                setCreating(false);
            }
        },
        [setNewConversationModal]
    );

    const handleClose = useCallback(() => {
        if (!creating) {
            setNewConversationModal(false);
        }
    }, [creating, setNewConversationModal]);

    if (!newConversationModalOpen) return null;

    return (
        <>
            {/* Backdrop */}
            <div
                className="live-chat__modal-backdrop"
                onClick={handleClose}
                aria-hidden="true"
            />

            {/* Modal */}
            <div
                className="live-chat__modal"
                role="dialog"
                aria-labelledby="new-conversation-title"
                aria-modal="true"
            >
                <div className="live-chat__modal-header">
                    <h2
                        id="new-conversation-title"
                        className="live-chat__modal-title"
                    >
                        New Conversation
                    </h2>
                    <button
                        type="button"
                        className="live-chat__button live-chat__button--icon"
                        onClick={handleClose}
                        disabled={creating}
                        aria-label="Close"
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
                            <path d="M5 5l10 10M15 5L5 15" />
                        </svg>
                    </button>
                </div>

                <div className="live-chat__modal-content">
                    <UserSearch
                        onSelectUser={handleSelectUser}
                        disabled={creating}
                    />
                </div>
            </div>
        </>
    );
};

NewConversation.displayName = "NewConversation";
