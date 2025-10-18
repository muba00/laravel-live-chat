import React, { createContext, useReducer, useContext, useCallback, useMemo } from 'react';
import type {
  ConversationsState,
  ConversationsAction,
  Conversation,
  Message,
  ApiError,
} from '../types';

const initialState: ConversationsState = {
  conversations: [],
  activeConversationId: null,
  loading: false,
  error: null,
  hasMore: true,
  currentPage: 1,
};

function conversationsReducer(
  state: ConversationsState,
  action: ConversationsAction
): ConversationsState {
  switch (action.type) {
    case 'CONVERSATIONS_LOADING':
      return { ...state, loading: true, error: null };

    case 'CONVERSATIONS_LOADED':
      return {
        ...state,
        loading: false,
        conversations:
          action.payload.page === 1
            ? action.payload.conversations
            : [...state.conversations, ...action.payload.conversations],
        currentPage: action.payload.page,
        hasMore: action.payload.hasMore,
      };

    case 'CONVERSATIONS_ERROR':
      return { ...state, loading: false, error: action.payload };

    case 'CONVERSATION_ADDED': {
      const exists = state.conversations.some((c) => c.id === action.payload.id);
      if (exists) {
        return {
          ...state,
          conversations: state.conversations.map((c) =>
            c.id === action.payload.id ? action.payload : c
          ),
        };
      }
      return {
        ...state,
        conversations: [action.payload, ...state.conversations],
      };
    }

    case 'CONVERSATION_UPDATED':
      return {
        ...state,
        conversations: state.conversations.map((c) =>
          c.id === action.payload.id ? { ...c, ...action.payload } : c
        ),
      };

    case 'CONVERSATION_DELETED':
      return {
        ...state,
        conversations: state.conversations.filter((c) => c.id !== action.payload),
        activeConversationId:
          state.activeConversationId === action.payload
            ? null
            : state.activeConversationId,
      };

    case 'SET_ACTIVE_CONVERSATION':
      return { ...state, activeConversationId: action.payload };

    case 'UPDATE_LAST_MESSAGE': {
      const { conversationId, message } = action.payload;
      return {
        ...state,
        conversations: state.conversations
          .map((c) => {
            if (c.id === conversationId) {
              return {
                ...c,
                lastMessage: message,
                lastMessageAt: message.createdAt,
              };
            }
            return c;
          })
          .sort((a, b) => {
            const aTime = a.lastMessageAt || a.createdAt;
            const bTime = b.lastMessageAt || b.createdAt;
            return new Date(bTime).getTime() - new Date(aTime).getTime();
          }),
      };
    }

    case 'INCREMENT_UNREAD':
      return {
        ...state,
        conversations: state.conversations.map((c) =>
          c.id === action.payload ? { ...c, unreadCount: c.unreadCount + 1 } : c
        ),
      };

    case 'CLEAR_UNREAD':
      return {
        ...state,
        conversations: state.conversations.map((c) =>
          c.id === action.payload ? { ...c, unreadCount: 0 } : c
        ),
      };

    case 'CONVERSATIONS_RESET':
      return initialState;

    default:
      return state;
  }
}

interface ConversationsContextValue {
  state: ConversationsState;
  dispatch: React.Dispatch<ConversationsAction>;
  setActiveConversation: (id: number | null) => void;
  updateLastMessage: (conversationId: number, message: Message) => void;
  incrementUnread: (conversationId: number) => void;
  clearUnread: (conversationId: number) => void;
  addConversation: (conversation: Conversation) => void;
  updateConversation: (conversation: Conversation) => void;
  deleteConversation: (id: number) => void;
}

const ConversationsContext = createContext<ConversationsContextValue | undefined>(undefined);

export function ConversationsProvider({ children }: { children: React.ReactNode }) {
  const [state, dispatch] = useReducer(conversationsReducer, initialState);

  const setActiveConversation = useCallback((id: number | null) => {
    dispatch({ type: 'SET_ACTIVE_CONVERSATION', payload: id });
  }, []);

  const updateLastMessage = useCallback((conversationId: number, message: Message) => {
    dispatch({ type: 'UPDATE_LAST_MESSAGE', payload: { conversationId, message } });
  }, []);

  const incrementUnread = useCallback((conversationId: number) => {
    dispatch({ type: 'INCREMENT_UNREAD', payload: conversationId });
  }, []);

  const clearUnread = useCallback((conversationId: number) => {
    dispatch({ type: 'CLEAR_UNREAD', payload: conversationId });
  }, []);

  const addConversation = useCallback((conversation: Conversation) => {
    dispatch({ type: 'CONVERSATION_ADDED', payload: conversation });
  }, []);

  const updateConversation = useCallback((conversation: Conversation) => {
    dispatch({ type: 'CONVERSATION_UPDATED', payload: conversation });
  }, []);

  const deleteConversation = useCallback((id: number) => {
    dispatch({ type: 'CONVERSATION_DELETED', payload: id });
  }, []);

  const value = useMemo(
    () => ({
      state,
      dispatch,
      setActiveConversation,
      updateLastMessage,
      incrementUnread,
      clearUnread,
      addConversation,
      updateConversation,
      deleteConversation,
    }),
    [
      state,
      setActiveConversation,
      updateLastMessage,
      incrementUnread,
      clearUnread,
      addConversation,
      updateConversation,
      deleteConversation,
    ]
  );

  return (
    <ConversationsContext.Provider value={value}>{children}</ConversationsContext.Provider>
  );
}

export function useConversations() {
  const context = useContext(ConversationsContext);
  if (!context) {
    throw new Error('useConversations must be used within ConversationsProvider');
  }
  return context;
}
