/**
 * Formatting Utilities
 *
 * Date, time, and text formatting helpers
 */

/**
 * Format a date/time string to relative time
 * e.g., "2 minutes ago", "Yesterday", "Dec 25"
 */
export function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    // Less than a minute
    if (diffInSeconds < 60) {
        return "Just now";
    }

    // Less than an hour
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes}m ago`;
    }

    // Less than a day
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours}h ago`;
    }

    // Less than a week
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) {
        if (diffInDays === 1) return "Yesterday";
        return `${diffInDays}d ago`;
    }

    // Format as date
    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: date.getFullYear() !== now.getFullYear() ? "numeric" : undefined,
    });
}

/**
 * Format timestamp for message display
 * e.g., "10:30 AM"
 */
export function formatMessageTime(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

/**
 * Format date for date separators
 * e.g., "Today", "Yesterday", "December 25, 2024"
 */
export function formatDateSeparator(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();

    // Reset time portions for comparison
    const dateOnly = new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate()
    );
    const nowOnly = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    const diffInDays = Math.floor(
        (nowOnly.getTime() - dateOnly.getTime()) / (1000 * 60 * 60 * 24)
    );

    if (diffInDays === 0) return "Today";
    if (diffInDays === 1) return "Yesterday";

    // Less than a week
    if (diffInDays < 7) {
        return date.toLocaleDateString("en-US", { weekday: "long" });
    }

    // Full date
    return date.toLocaleDateString("en-US", {
        month: "long",
        day: "numeric",
        year: date.getFullYear() !== now.getFullYear() ? "numeric" : undefined,
    });
}

/**
 * Truncate text to a maximum length
 */
export function truncate(text: string, maxLength: number = 50): string {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - 3) + "...";
}

/**
 * Linkify text - convert URLs to clickable links
 */
export function linkify(text: string): string {
    const urlPattern =
        /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)/gi;

    return text.replace(urlPattern, (url) => {
        return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="live-chat__link">${url}</a>`;
    });
}

/**
 * Sanitize HTML to prevent XSS
 */
export function sanitizeHTML(html: string): string {
    const div = document.createElement("div");
    div.textContent = html;
    return div.innerHTML;
}
