/**
 * API Client for Laravel Live Chat
 * 
 * Plain fetch-based HTTP client with no external dependencies
 * Handles authentication, errors, and provides type-safe methods
 */

import type {
  ApiResponse,
  ApiError,
  PaginatedResponse,
  Conversation,
  Message,
  User,
} from '../types';

export class ApiClient {
  private baseUrl: string;
  private headers: Record<string, string>;
  private timeout: number;

  constructor(baseUrl: string, headers: Record<string, string> = {}, timeout: number = 30000) {
    this.baseUrl = baseUrl.replace(/\/$/, '');
    this.headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...headers,
    };
    this.timeout = timeout;
  }

  /**
   * Set custom headers (e.g., CSRF token, Authorization)
   */
  setHeader(key: string, value: string): void {
    this.headers[key] = value;
  }

  /**
   * Remove a header
   */
  removeHeader(key: string): void {
    delete this.headers[key];
  }

  /**
   * Generic fetch wrapper with timeout and error handling
   */
  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.timeout);

    try {
      const url = `${this.baseUrl}${endpoint}`;
      const response = await fetch(url, {
        ...options,
        headers: {
          ...this.headers,
          ...options.headers,
        },
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        await this.handleErrorResponse(response);
      }

      const contentType = response.headers.get('content-type');
      if (contentType?.includes('application/json')) {
        return await response.json();
      }

      return {} as T;
    } catch (error) {
      clearTimeout(timeoutId);

      if (error instanceof Error) {
        if (error.name === 'AbortError') {
          throw this.createApiError('Request timeout', 408);
        }
        throw this.createApiError(error.message, 0);
      }

      throw this.createApiError('An unknown error occurred', 0);
    }
  }

  /**
   * Handle error responses from the API
   */
  private async handleErrorResponse(response: Response): Promise<never> {
    let errorData: { message?: string; errors?: Record<string, string[]> } = {};

    try {
      errorData = await response.json();
    } catch {
      // If response is not JSON, use status text
    }

    const message = errorData.message || response.statusText || 'An error occurred';
    const errors = errorData.errors;

    throw this.createApiError(message, response.status, errors);
  }

  /**
   * Create a standardized API error object
   */
  private createApiError(
    message: string,
    statusCode: number,
    errors?: Record<string, string[]>
  ): ApiError {
    return { message, statusCode, errors };
  }

  /**
   * GET request
   */
  private async get<T>(endpoint: string, params?: Record<string, string | number>): Promise<T> {
    let url = endpoint;
    if (params) {
      const queryString = new URLSearchParams(
        Object.entries(params).map(([key, value]) => [key, String(value)])
      ).toString();
      url = `${endpoint}?${queryString}`;
    }

    return this.request<T>(url, { method: 'GET' });
  }

  /**
   * POST request
   */
  private async post<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * PUT request
   */
  private async put<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * PATCH request
   */
  private async patch<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * DELETE request
   */
  private async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'DELETE' });
  }

  // ============================================
  // Conversation Endpoints
  // ============================================

  /**
   * Get paginated list of conversations
   */
  async getConversations(page: number = 1, perPage: number = 20): Promise<PaginatedResponse<Conversation>> {
    return this.get<PaginatedResponse<Conversation>>('/conversations', { page, per_page: perPage });
  }

  /**
   * Get a single conversation by ID
   */
  async getConversation(conversationId: number): Promise<ApiResponse<Conversation>> {
    return this.get<ApiResponse<Conversation>>(`/conversations/${conversationId}`);
  }

  /**
   * Create a new conversation
   */
  async createConversation(participantIds: number[]): Promise<ApiResponse<Conversation>> {
    return this.post<ApiResponse<Conversation>>('/conversations', { participant_ids: participantIds });
  }

  /**
   * Delete a conversation
   */
  async deleteConversation(conversationId: number): Promise<ApiResponse<void>> {
    return this.delete<ApiResponse<void>>(`/conversations/${conversationId}`);
  }

  /**
   * Search conversations
   */
  async searchConversations(query: string, page: number = 1): Promise<PaginatedResponse<Conversation>> {
    return this.get<PaginatedResponse<Conversation>>('/conversations/search', { q: query, page });
  }

  /**
   * Mark conversation as read
   */
  async markConversationAsRead(conversationId: number): Promise<ApiResponse<void>> {
    return this.post<ApiResponse<void>>(`/conversations/${conversationId}/read`);
  }

  // ============================================
  // Message Endpoints
  // ============================================

  /**
   * Get paginated messages for a conversation
   */
  async getMessages(conversationId: number, page: number = 1, perPage: number = 50): Promise<PaginatedResponse<Message>> {
    return this.get<PaginatedResponse<Message>>(`/conversations/${conversationId}/messages`, {
      page,
      per_page: perPage,
    });
  }

  /**
   * Send a new message
   */
  async sendMessage(conversationId: number, content: string): Promise<ApiResponse<Message>> {
    return this.post<ApiResponse<Message>>(`/conversations/${conversationId}/messages`, { content });
  }

  /**
   * Delete a message
   */
  async deleteMessage(conversationId: number, messageId: number): Promise<ApiResponse<void>> {
    return this.delete<ApiResponse<void>>(`/conversations/${conversationId}/messages/${messageId}`);
  }

  /**
   * Mark message as read
   */
  async markMessageAsRead(conversationId: number, messageId: number): Promise<ApiResponse<void>> {
    return this.post<ApiResponse<void>>(`/conversations/${conversationId}/messages/${messageId}/read`);
  }

  // ============================================
  // User Endpoints
  // ============================================

  /**
   * Search users (for starting new conversations)
   */
  async searchUsers(query: string, page: number = 1): Promise<PaginatedResponse<User>> {
    return this.get<PaginatedResponse<User>>('/users/search', { q: query, page });
  }

  /**
   * Get current user info
   */
  async getCurrentUser(): Promise<ApiResponse<User>> {
    return this.get<ApiResponse<User>>('/user');
  }

  // ============================================
  // Typing Indicator Endpoints
  // ============================================

  /**
   * Send typing indicator
   */
  async sendTypingIndicator(conversationId: number, isTyping: boolean): Promise<ApiResponse<void>> {
    return this.post<ApiResponse<void>>(`/conversations/${conversationId}/typing`, { is_typing: isTyping });
  }
}

/**
 * Create an API client instance with CSRF token from meta tag
 */
export function createApiClient(baseUrl: string): ApiClient {
  const headers: Record<string, string> = {};

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    headers['X-CSRF-TOKEN'] = csrfToken;
  }

  const authToken = localStorage.getItem('auth_token');
  if (authToken) {
    headers['Authorization'] = `Bearer ${authToken}`;
  }

  return new ApiClient(baseUrl, headers);
}
