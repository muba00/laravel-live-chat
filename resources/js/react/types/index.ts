/**
 * TypeScript type definitions for Laravel Live Chat
 */

// ============================================
// User Types
// ============================================

export interface User {
    id: number;
    name: string;
    email?: string;
    avatar?: string;
    status?: "online" | "offline" | "away";
    lastSeenAt?: string;
}

// ============================================
// Conversation Types
// ============================================

export interface Conversation {
    id: number;
    participantIds: number[];
    participants: User[];
    lastMessage?: Message;
    lastMessageAt?: string;
    unreadCount: number;
    createdAt: string;
    updatedAt: string;
}

export interface ConversationMeta {
    id: number;
    participantNames: string;
    lastMessagePreview?: string;
    lastMessageAt?: string;
    unreadCount: number;
}

// ============================================
// Message Types
// ============================================

export interface Message {
    id: number;
    conversationId: number;
    senderId: number;
    sender?: User;
    content: string;
    readAt?: string;
    createdAt: string;
    updatedAt: string;
    status?: "sending" | "sent" | "failed";
    tempId?: string;
}

export interface OptimisticMessage extends Message {
    tempId: string;
    status: "sending";
}

// ============================================
// Typing Indicator Types
// ============================================

export interface TypingIndicator {
    conversationId: number;
    userId: number;
    userName: string;
    timestamp: number;
}

// ============================================
// API Response Types
// ============================================

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    };
    links?: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface ApiResponse<T> {
    data: T;
    message?: string;
}

export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
    statusCode?: number;
}

// ============================================
// WebSocket Event Types
// ============================================

export interface MessageSentEvent {
    message: Message;
    conversation: Conversation;
}

export interface MessageReadEvent {
    messageId: number;
    conversationId: number;
    userId: number;
    readAt: string;
}

export interface TypingEvent {
    conversationId: number;
    user: User;
    isTyping: boolean;
}

export interface UserStatusEvent {
    userId: number;
    status: "online" | "offline" | "away";
    lastSeenAt?: string;
}

// ============================================
// Context State Types
// ============================================

export interface ConversationsState {
    conversations: Conversation[];
    activeConversationId: number | null;
    loading: boolean;
    error: ApiError | null;
    hasMore: boolean;
    currentPage: number;
}

export interface MessagesState {
    messagesByConversation: Record<number, Message[]>;
    loading: boolean;
    error: ApiError | null;
    hasMore: Record<number, boolean>;
    currentPage: Record<number, number>;
}

export interface ConnectionState {
    connected: boolean;
    connecting: boolean;
    error: string | null;
    reconnecting: boolean;
    reconnectAttempts: number;
}

export interface ToastMessage {
    id: string;
    message: string;
    type: "success" | "error" | "info" | "warning";
    duration?: number;
}

export interface ConfirmDialog {
    id: string;
    title: string;
    message: string;
    resolve: (value: boolean) => void;
}

export interface UIState {
    sidebarOpen: boolean;
    newConversationModalOpen: boolean;
    searchQuery: string;
    theme: "light" | "dark";
    isMobile: boolean;
    toast: ToastMessage | null;
    confirm: ConfirmDialog | null;
}

// ============================================
// Action Types
// ============================================

export type ConversationsAction =
    | { type: "CONVERSATIONS_LOADING" }
    | {
          type: "CONVERSATIONS_LOADED";
          payload: {
              conversations: Conversation[];
              page: number;
              hasMore: boolean;
          };
      }
    | { type: "CONVERSATIONS_ERROR"; payload: ApiError }
    | { type: "CONVERSATION_ADDED"; payload: Conversation }
    | { type: "CONVERSATION_UPDATED"; payload: Conversation }
    | { type: "CONVERSATION_DELETED"; payload: number }
    | { type: "SET_ACTIVE_CONVERSATION"; payload: number | null }
    | {
          type: "UPDATE_LAST_MESSAGE";
          payload: { conversationId: number; message: Message };
      }
    | { type: "INCREMENT_UNREAD"; payload: number }
    | { type: "CLEAR_UNREAD"; payload: number }
    | { type: "CONVERSATIONS_RESET" };

export type MessagesAction =
    | { type: "MESSAGES_LOADING"; payload: number }
    | {
          type: "MESSAGES_LOADED";
          payload: {
              conversationId: number;
              messages: Message[];
              page: number;
              hasMore: boolean;
          };
      }
    | { type: "MESSAGES_ERROR"; payload: ApiError }
    | { type: "MESSAGE_ADDED"; payload: Message }
    | { type: "MESSAGE_UPDATED"; payload: Message }
    | {
          type: "MESSAGE_DELETED";
          payload: { conversationId: number; messageId: number };
      }
    | { type: "MESSAGE_SENDING"; payload: OptimisticMessage }
    | { type: "MESSAGE_SENT"; payload: { tempId: string; message: Message } }
    | { type: "MESSAGE_FAILED"; payload: { tempId: string; error: string } }
    | {
          type: "MESSAGE_READ";
          payload: {
              conversationId: number;
              messageId: number;
              readAt: string;
          };
      }
    | { type: "MESSAGES_RESET" };

export type ConnectionAction =
    | { type: "CONNECTION_CONNECTING" }
    | { type: "CONNECTION_CONNECTED" }
    | { type: "CONNECTION_DISCONNECTED" }
    | { type: "CONNECTION_ERROR"; payload: string }
    | { type: "CONNECTION_RECONNECTING"; payload: number }
    | { type: "CONNECTION_RESET" };

export type UIAction =
    | { type: "TOGGLE_SIDEBAR" }
    | { type: "SET_SIDEBAR"; payload: boolean }
    | { type: "TOGGLE_NEW_CONVERSATION_MODAL" }
    | { type: "SET_NEW_CONVERSATION_MODAL"; payload: boolean }
    | { type: "SET_SEARCH_QUERY"; payload: string }
    | { type: "SET_THEME"; payload: "light" | "dark" }
    | { type: "SET_IS_MOBILE"; payload: boolean }
    | { type: "SHOW_TOAST"; payload: ToastMessage }
    | { type: "HIDE_TOAST" }
    | { type: "SHOW_CONFIRM"; payload: ConfirmDialog }
    | { type: "HIDE_CONFIRM" }
    | { type: "UI_RESET" };

// ============================================
// Component Prop Types
// ============================================

export interface LiveChatProps {
    userId: number;
    apiUrl?: string;
    wsHost?: string;
    wsPort?: number;
    wsKey?: string;
    wsForceTLS?: boolean;
    theme?: "light" | "dark";
    height?: string;
    width?: string;
    className?: string;
    onError?: (error: ApiError) => void;
}

// ============================================
// Hook Return Types
// ============================================

export interface UseConversationsReturn {
    conversations: Conversation[];
    activeConversationId: number | null;
    loading: boolean;
    error: ApiError | null;
    hasMore: boolean;
    loadMore: () => Promise<void>;
    setActiveConversation: (id: number | null) => void;
    deleteConversation: (id: number) => Promise<void>;
    markAsRead: (conversationId: number) => Promise<void>;
    refresh: () => Promise<void>;
}

export interface UseMessagesReturn {
    messages: Message[];
    loading: boolean;
    error: ApiError | null;
    hasMore: boolean;
    loadMore: () => Promise<void>;
    sendMessage: (content: string) => Promise<void>;
    deleteMessage: (messageId: number) => Promise<void>;
    refresh: () => Promise<void>;
}

export interface UseConnectionReturn {
    connected: boolean;
    connecting: boolean;
    error: string | null;
    reconnecting: boolean;
    reconnect: () => void;
}

export interface UseUIReturn {
    sidebarOpen: boolean;
    newConversationModalOpen: boolean;
    searchQuery: string;
    theme: "light" | "dark";
    isMobile: boolean;
    toggleSidebar: () => void;
    setSidebar: (open: boolean) => void;
    toggleNewConversationModal: () => void;
    setNewConversationModal: (open: boolean) => void;
    setSearchQuery: (query: string) => void;
    setTheme: (theme: "light" | "dark") => void;
}

// ============================================
// Configuration Types
// ============================================

export interface LiveChatConfig {
    userId: number;
    apiUrl: string;
    wsHost?: string;
    wsPort?: number;
    wsKey?: string;
    wsForceTLS?: boolean;
}

export interface ApiClientConfig {
    baseUrl: string;
    headers?: Record<string, string>;
    timeout?: number;
}

export interface WebSocketConfig {
    host?: string;
    port?: number;
    key?: string;
    forceTLS?: boolean;
    authEndpoint?: string;
}
