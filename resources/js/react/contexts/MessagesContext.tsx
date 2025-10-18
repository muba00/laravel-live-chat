import React, { createContext, useReducer, useContext, useCallback, useMemo } from 'react';
import type {
  MessagesState,
  MessagesAction,
  Message,
  OptimisticMessage,
  ApiError,
} from '../types';

const initialState: MessagesState = {
  messagesByConversation: {},
  loading: false,
  error: null,
  hasMore: {},
  currentPage: {},
};

function messagesReducer(state: MessagesState, action: MessagesAction): MessagesState {
  switch (action.type) {
    case 'MESSAGES_LOADING':
      return { ...state, loading: true, error: null };

    case 'MESSAGES_LOADED': {
      const { conversationId, messages, page, hasMore } = action.payload;
      const existingMessages = state.messagesByConversation[conversationId] || [];

      return {
        ...state,
        loading: false,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: page === 1 ? messages : [...messages, ...existingMessages],
        },
        currentPage: {
          ...state.currentPage,
          [conversationId]: page,
        },
        hasMore: {
          ...state.hasMore,
          [conversationId]: hasMore,
        },
      };
    }

    case 'MESSAGES_ERROR':
      return { ...state, loading: false, error: action.payload };

    case 'MESSAGE_ADDED': {
      const { conversationId } = action.payload;
      const messages = state.messagesByConversation[conversationId] || [];
      
      const exists = messages.some((m) => m.id === action.payload.id);
      if (exists) {
        return state;
      }

      return {
        ...state,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: [...messages, action.payload],
        },
      };
    }

    case 'MESSAGE_UPDATED': {
      const { conversationId, id } = action.payload;
      const messages = state.messagesByConversation[conversationId] || [];

      return {
        ...state,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: messages.map((m) => (m.id === id ? action.payload : m)),
        },
      };
    }

    case 'MESSAGE_DELETED': {
      const { conversationId, messageId } = action.payload;
      const messages = state.messagesByConversation[conversationId] || [];

      return {
        ...state,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: messages.filter((m) => m.id !== messageId),
        },
      };
    }

    case 'MESSAGE_SENDING': {
      const { conversationId, tempId } = action.payload;
      const messages = state.messagesByConversation[conversationId] || [];

      const exists = messages.some((m) => m.tempId === tempId);
      if (exists) {
        return state;
      }

      return {
        ...state,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: [...messages, action.payload],
        },
      };
    }

    case 'MESSAGE_SENT': {
      const { tempId, message } = action.payload;
      const { conversationId } = message;
      const messages = state.messagesByConversation[conversationId] || [];

      return {
        ...state,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: messages.map((m) =>
            m.tempId === tempId ? message : m
          ),
        },
      };
    }

    case 'MESSAGE_FAILED': {
      const { tempId } = action.payload;
      const conversationId = Object.keys(state.messagesByConversation).find((cid) =>
        state.messagesByConversation[parseInt(cid)].some((m) => m.tempId === tempId)
      );

      if (!conversationId) {
        return state;
      }

      const messages = state.messagesByConversation[parseInt(conversationId)];

      return {
        ...state,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: messages.map((m) =>
            m.tempId === tempId ? { ...m, status: 'failed' } : m
          ),
        },
      };
    }

    case 'MESSAGE_READ': {
      const { conversationId, messageId, readAt } = action.payload;
      const messages = state.messagesByConversation[conversationId] || [];

      return {
        ...state,
        messagesByConversation: {
          ...state.messagesByConversation,
          [conversationId]: messages.map((m) =>
            m.id === messageId ? { ...m, readAt } : m
          ),
        },
      };
    }

    case 'MESSAGES_RESET':
      return initialState;

    default:
      return state;
  }
}

interface MessagesContextValue {
  state: MessagesState;
  dispatch: React.Dispatch<MessagesAction>;
  getMessages: (conversationId: number) => Message[];
  addMessage: (message: Message) => void;
  updateMessage: (message: Message) => void;
  deleteMessage: (conversationId: number, messageId: number) => void;
  sendingMessage: (message: OptimisticMessage) => void;
  messageSent: (tempId: string, message: Message) => void;
  messageFailed: (tempId: string, error: string) => void;
  markMessageRead: (conversationId: number, messageId: number, readAt: string) => void;
}

const MessagesContext = createContext<MessagesContextValue | undefined>(undefined);

export function MessagesProvider({ children }: { children: React.ReactNode }) {
  const [state, dispatch] = useReducer(messagesReducer, initialState);

  const getMessages = useCallback(
    (conversationId: number): Message[] => {
      return state.messagesByConversation[conversationId] || [];
    },
    [state.messagesByConversation]
  );

  const addMessage = useCallback((message: Message) => {
    dispatch({ type: 'MESSAGE_ADDED', payload: message });
  }, []);

  const updateMessage = useCallback((message: Message) => {
    dispatch({ type: 'MESSAGE_UPDATED', payload: message });
  }, []);

  const deleteMessage = useCallback((conversationId: number, messageId: number) => {
    dispatch({ type: 'MESSAGE_DELETED', payload: { conversationId, messageId } });
  }, []);

  const sendingMessage = useCallback((message: OptimisticMessage) => {
    dispatch({ type: 'MESSAGE_SENDING', payload: message });
  }, []);

  const messageSent = useCallback((tempId: string, message: Message) => {
    dispatch({ type: 'MESSAGE_SENT', payload: { tempId, message } });
  }, []);

  const messageFailed = useCallback((tempId: string, error: string) => {
    dispatch({ type: 'MESSAGE_FAILED', payload: { tempId, error } });
  }, []);

  const markMessageRead = useCallback(
    (conversationId: number, messageId: number, readAt: string) => {
      dispatch({ type: 'MESSAGE_READ', payload: { conversationId, messageId, readAt } });
    },
    []
  );

  const value = useMemo(
    () => ({
      state,
      dispatch,
      getMessages,
      addMessage,
      updateMessage,
      deleteMessage,
      sendingMessage,
      messageSent,
      messageFailed,
      markMessageRead,
    }),
    [
      state,
      getMessages,
      addMessage,
      updateMessage,
      deleteMessage,
      sendingMessage,
      messageSent,
      messageFailed,
      markMessageRead,
    ]
  );

  return <MessagesContext.Provider value={value}>{children}</MessagesContext.Provider>;
}

export function useMessages() {
  const context = useContext(MessagesContext);
  if (!context) {
    throw new Error('useMessages must be used within MessagesProvider');
  }
  return context;
}
