/**
 * WebSocket Connection Manager for Laravel Live Chat
 * 
 * Wraps Laravel Echo and Pusher to provide a clean interface
 * for managing real-time subscriptions and events
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type {
  WebSocketConfig,
  MessageSentEvent,
  MessageReadEvent,
  TypingEvent,
  UserStatusEvent,
} from '../types';

declare global {
  interface Window {
    Pusher: typeof Pusher;
    Echo?: Echo;
  }
}

window.Pusher = Pusher;

type EventCallback<T = unknown> = (data: T) => void;

export class WebSocketManager {
  private echo: Echo | null = null;
  private config: Required<WebSocketConfig>;
  private subscriptions: Map<string, unknown> = new Map();
  private eventCallbacks: Map<string, Set<EventCallback>> = new Map();
  private connected: boolean = false;
  private connecting: boolean = false;

  constructor(config: WebSocketConfig) {
    this.config = {
      host: config.host || window.location.hostname,
      port: config.port || (window.location.protocol === 'https:' ? 443 : 6001),
      key: config.key || 'live-chat-key',
      forceTLS: config.forceTLS ?? window.location.protocol === 'https:',
      authEndpoint: config.authEndpoint || '/broadcasting/auth',
    };
  }

  /**
   * Initialize the Echo connection
   */
  connect(userId: number): Promise<void> {
    return new Promise((resolve, reject) => {
      if (this.echo && this.connected) {
        resolve();
        return;
      }

      if (this.connecting) {
        const checkConnection = setInterval(() => {
          if (this.connected) {
            clearInterval(checkConnection);
            resolve();
          } else if (!this.connecting) {
            clearInterval(checkConnection);
            reject(new Error('Connection failed'));
          }
        }, 100);
        return;
      }

      this.connecting = true;

      try {
        this.echo = new Echo({
          broadcaster: 'pusher',
          key: this.config.key,
          wsHost: this.config.host,
          wsPort: this.config.port,
          wssPort: this.config.port,
          forceTLS: this.config.forceTLS,
          enabledTransports: ['ws', 'wss'],
          authEndpoint: this.config.authEndpoint,
          auth: {
            headers: this.getAuthHeaders(),
          },
        });

        window.Echo = this.echo;

        this.echo.connector.pusher.connection.bind('connected', () => {
          this.connected = true;
          this.connecting = false;
          this.subscribeToUserChannel(userId);
          resolve();
        });

        this.echo.connector.pusher.connection.bind('error', (error: Error) => {
          this.connected = false;
          this.connecting = false;
          reject(error);
        });

        this.echo.connector.pusher.connection.bind('disconnected', () => {
          this.connected = false;
        });
      } catch (error) {
        this.connecting = false;
        reject(error);
      }
    });
  }

  /**
   * Disconnect from Echo
   */
  disconnect(): void {
    if (this.echo) {
      this.subscriptions.forEach((_, channel) => {
        this.echo?.leave(channel);
      });
      this.subscriptions.clear();
      this.eventCallbacks.clear();
      this.echo.disconnect();
      this.echo = null;
      this.connected = false;
      this.connecting = false;
    }
  }

  /**
   * Check if connected
   */
  isConnected(): boolean {
    return this.connected;
  }

  /**
   * Get authentication headers for requests
   */
  private getAuthHeaders(): Record<string, string> {
    const headers: Record<string, string> = {};

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const authToken = localStorage.getItem('auth_token');
    if (authToken) {
      headers['Authorization'] = `Bearer ${authToken}`;
    }

    return headers;
  }

  /**
   * Subscribe to user's private channel
   */
  private subscribeToUserChannel(userId: number): void {
    if (!this.echo) return;

    const channelName = `private-user.${userId}`;
    
    if (this.subscriptions.has(channelName)) {
      return;
    }

    const channel = this.echo.private(channelName);
    this.subscriptions.set(channelName, channel);
  }

  /**
   * Subscribe to a conversation channel
   */
  subscribeToConversation(conversationId: number): void {
    if (!this.echo) {
      console.warn('Echo not connected. Call connect() first.');
      return;
    }

    const channelName = `private-conversation.${conversationId}`;
    
    if (this.subscriptions.has(channelName)) {
      return;
    }

    const channel = this.echo.private(channelName);
    this.subscriptions.set(channelName, channel);

    channel
      .listen('.message.sent', (event: MessageSentEvent) => {
        this.triggerEvent('message.sent', event);
      })
      .listen('.message.read', (event: MessageReadEvent) => {
        this.triggerEvent('message.read', event);
      })
      .listen('.typing', (event: TypingEvent) => {
        this.triggerEvent('typing', event);
      });
  }

  /**
   * Unsubscribe from a conversation channel
   */
  unsubscribeFromConversation(conversationId: number): void {
    const channelName = `private-conversation.${conversationId}`;
    
    if (this.echo && this.subscriptions.has(channelName)) {
      this.echo.leave(channelName);
      this.subscriptions.delete(channelName);
    }
  }

  /**
   * Subscribe to user presence channel (for online status)
   */
  subscribeToPresence(channelName: string = 'chat-users'): void {
    if (!this.echo) return;

    const presenceChannelName = `presence-${channelName}`;
    
    if (this.subscriptions.has(presenceChannelName)) {
      return;
    }

    const channel = this.echo.join(presenceChannelName);
    this.subscriptions.set(presenceChannelName, channel);

    channel
      .here((users: unknown[]) => {
        this.triggerEvent('presence.here', users);
      })
      .joining((user: unknown) => {
        this.triggerEvent('presence.joining', user);
      })
      .leaving((user: unknown) => {
        this.triggerEvent('presence.leaving', user);
      });
  }

  /**
   * Register an event listener
   */
  on<T = unknown>(eventName: string, callback: EventCallback<T>): () => void {
    if (!this.eventCallbacks.has(eventName)) {
      this.eventCallbacks.set(eventName, new Set());
    }

    const callbacks = this.eventCallbacks.get(eventName)!;
    callbacks.add(callback as EventCallback);

    return () => {
      callbacks.delete(callback as EventCallback);
      if (callbacks.size === 0) {
        this.eventCallbacks.delete(eventName);
      }
    };
  }

  /**
   * Remove an event listener
   */
  off(eventName: string, callback?: EventCallback): void {
    if (!callback) {
      this.eventCallbacks.delete(eventName);
      return;
    }

    const callbacks = this.eventCallbacks.get(eventName);
    if (callbacks) {
      callbacks.delete(callback);
      if (callbacks.size === 0) {
        this.eventCallbacks.delete(eventName);
      }
    }
  }

  /**
   * Trigger an event to all registered listeners
   */
  private triggerEvent<T>(eventName: string, data: T): void {
    const callbacks = this.eventCallbacks.get(eventName);
    if (callbacks) {
      callbacks.forEach((callback) => {
        try {
          callback(data);
        } catch (error) {
          console.error(`Error in event callback for ${eventName}:`, error);
        }
      });
    }
  }

  /**
   * Get the Echo instance (for advanced use cases)
   */
  getEcho(): Echo | null {
    return this.echo;
  }

  /**
   * Get connection state
   */
  getConnectionState(): 'connected' | 'connecting' | 'disconnected' {
    if (this.connected) return 'connected';
    if (this.connecting) return 'connecting';
    return 'disconnected';
  }
}

/**
 * Create a WebSocket manager instance
 */
export function createWebSocketManager(config: WebSocketConfig): WebSocketManager {
  return new WebSocketManager(config);
}
