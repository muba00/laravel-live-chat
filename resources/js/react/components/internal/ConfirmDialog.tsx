/**
 * ConfirmDialog Component
 *
 * Modal confirmation dialog for destructive actions.
 * Integrates with UIContext for state management.
 */

import React, { useCallback } from "react";
import { useUI } from "../../contexts/UIContext";

export const ConfirmDialog: React.FC = () => {
    const { confirm, hideConfirm } = useUI();

    const handleConfirm = useCallback(() => {
        if (confirm?.resolve) {
            confirm.resolve(true);
            hideConfirm();
        }
    }, [confirm, hideConfirm]);

    const handleCancel = useCallback(() => {
        if (confirm?.resolve) {
            confirm.resolve(false);
            hideConfirm();
        }
    }, [confirm, hideConfirm]);

    const handleBackdropClick = useCallback(
        (e: React.MouseEvent) => {
            if (e.target === e.currentTarget) {
                handleCancel();
            }
        },
        [handleCancel]
    );

    if (!confirm) return null;

    return (
        <div
            className="live-chat__modal-backdrop"
            onClick={handleBackdropClick}
            role="dialog"
            aria-modal="true"
            aria-labelledby="confirm-title"
            aria-describedby="confirm-message"
        >
            <div className="live-chat__confirm-dialog">
                <div className="live-chat__confirm-header">
                    <h3 id="confirm-title" className="live-chat__confirm-title">
                        {confirm.title}
                    </h3>
                </div>

                <div className="live-chat__confirm-body">
                    <p
                        id="confirm-message"
                        className="live-chat__confirm-message"
                    >
                        {confirm.message}
                    </p>
                </div>

                <div className="live-chat__confirm-footer">
                    <button
                        type="button"
                        className="live-chat__button live-chat__button--secondary"
                        onClick={handleCancel}
                        autoFocus
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        className="live-chat__button live-chat__button--danger"
                        onClick={handleConfirm}
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    );
};

ConfirmDialog.displayName = "ConfirmDialog";
