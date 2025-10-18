import React, { createContext, useReducer, useContext, useCallback, useMemo } from 'react';
import type { ConnectionState, ConnectionAction } from '../types';

const initialState: ConnectionState = {
  connected: false,
  connecting: false,
  error: null,
  reconnecting: false,
  reconnectAttempts: 0,
};

function connectionReducer(
  state: ConnectionState,
  action: ConnectionAction
): ConnectionState {
  switch (action.type) {
    case 'CONNECTION_CONNECTING':
      return {
        ...state,
        connecting: true,
        error: null,
        reconnecting: false,
      };

    case 'CONNECTION_CONNECTED':
      return {
        ...state,
        connected: true,
        connecting: false,
        error: null,
        reconnecting: false,
        reconnectAttempts: 0,
      };

    case 'CONNECTION_DISCONNECTED':
      return {
        ...state,
        connected: false,
        connecting: false,
        reconnecting: false,
      };

    case 'CONNECTION_ERROR':
      return {
        ...state,
        connected: false,
        connecting: false,
        error: action.payload,
      };

    case 'CONNECTION_RECONNECTING':
      return {
        ...state,
        connected: false,
        connecting: false,
        reconnecting: true,
        reconnectAttempts: action.payload,
      };

    case 'CONNECTION_RESET':
      return initialState;

    default:
      return state;
  }
}

interface ConnectionContextValue {
  state: ConnectionState;
  dispatch: React.Dispatch<ConnectionAction>;
  setConnecting: () => void;
  setConnected: () => void;
  setDisconnected: () => void;
  setError: (error: string) => void;
  setReconnecting: (attempts: number) => void;
}

const ConnectionContext = createContext<ConnectionContextValue | undefined>(undefined);

export function ConnectionProvider({ children }: { children: React.ReactNode }) {
  const [state, dispatch] = useReducer(connectionReducer, initialState);

  const setConnecting = useCallback(() => {
    dispatch({ type: 'CONNECTION_CONNECTING' });
  }, []);

  const setConnected = useCallback(() => {
    dispatch({ type: 'CONNECTION_CONNECTED' });
  }, []);

  const setDisconnected = useCallback(() => {
    dispatch({ type: 'CONNECTION_DISCONNECTED' });
  }, []);

  const setError = useCallback((error: string) => {
    dispatch({ type: 'CONNECTION_ERROR', payload: error });
  }, []);

  const setReconnecting = useCallback((attempts: number) => {
    dispatch({ type: 'CONNECTION_RECONNECTING', payload: attempts });
  }, []);

  const value = useMemo(
    () => ({
      state,
      dispatch,
      setConnecting,
      setConnected,
      setDisconnected,
      setError,
      setReconnecting,
    }),
    [state, setConnecting, setConnected, setDisconnected, setError, setReconnecting]
  );

  return <ConnectionContext.Provider value={value}>{children}</ConnectionContext.Provider>;
}

export function useConnection() {
  const context = useContext(ConnectionContext);
  if (!context) {
    throw new Error('useConnection must be used within ConnectionProvider');
  }
  return context;
}
