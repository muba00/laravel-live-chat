/**
 * Laravel Live Chat - React Package
 *
 * Main entry point for the React components library
 */

// Import styles for bundling
import "./styles/index.css";

// Main Component
export { LiveChat } from "./components";

// Contexts (for advanced usage)
export {
    ConversationsProvider,
    useConversations,
    MessagesProvider,
    useMessages,
    ConnectionProvider,
    useConnection,
    UIProvider,
    useUI,
} from "./contexts";

// API & WebSocket
export {
    ApiClient,
    createApiClient,
    WebSocketManager,
    createWebSocketManager,
} from "./lib";

// Types
export type {
    User,
    Conversation,
    ConversationMeta,
    Message,
    OptimisticMessage,
    TypingIndicator,
    PaginatedResponse,
    ApiResponse,
    ApiError,
    MessageSentEvent,
    MessageReadEvent,
    TypingEvent,
    UserStatusEvent,
    ConversationsState,
    MessagesState,
    ConnectionState,
    UIState,
    LiveChatProps,
    UseConversationsReturn,
    UseMessagesReturn,
    UseConnectionReturn,
    UseUIReturn,
    LiveChatConfig,
    ApiClientConfig,
    WebSocketConfig,
} from "./types";
