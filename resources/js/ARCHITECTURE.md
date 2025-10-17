# Laravel Live Chat - React Package Architecture

## 🏗️ System Architecture Overview

```
┌──────────────────────────────────────────────────────────────┐
│                      Laravel Backend                          │
│  ┌────────────────┐           ┌──────────────────────┐       │
│  │  REST API      │           │  WebSocket (Echo)    │       │
│  │  Endpoints     │           │  + Pusher/Redis      │       │
│  └────────┬───────┘           └──────────┬───────────┘       │
└───────────┼────────────────────────────────┼──────────────────┘
            │                                │
            │ HTTP                           │ WebSocket
            ▼                                ▼
┌──────────────────────────────────────────────────────────────┐
│                    React Package (Browser)                    │
│                                                               │
│  ┌────────────────────────────────────────────────────────┐  │
│  │              Core Libraries (lib/)                      │  │
│  │  ┌──────────────────┐      ┌──────────────────────┐   │  │
│  │  │  ApiClient       │      │  WebSocketManager    │   │  │
│  │  │  (fetch)         │      │  (Laravel Echo)      │   │  │
│  │  └────────┬─────────┘      └──────────┬───────────┘   │  │
│  └───────────┼────────────────────────────┼───────────────┘  │
│              │                            │                   │
│              ▼                            ▼                   │
│  ┌────────────────────────────────────────────────────────┐  │
│  │              State Management (contexts/)               │  │
│  │                                                         │  │
│  │  ┌──────────────────────────────────────────────────┐  │  │
│  │  │          UIContext (useReducer)                  │  │  │
│  │  │  • Theme (light/dark)                            │  │  │
│  │  │  • Sidebar open/closed                           │  │  │
│  │  │  • Modal visibility                              │  │  │
│  │  │  • Search query                                  │  │  │
│  │  │  • Mobile detection                              │  │  │
│  │  └───────────────┬──────────────────────────────────┘  │  │
│  │                  │                                      │  │
│  │  ┌───────────────▼──────────────────────────────────┐  │  │
│  │  │     ConversationsContext (useReducer)            │  │  │
│  │  │  • Conversation list                             │  │  │
│  │  │  • Active conversation                           │  │  │
│  │  │  • Unread counts                                 │  │  │
│  │  │  • Sorting by last message                       │  │  │
│  │  │  • Optimistic updates                            │  │  │
│  │  └───────────────┬──────────────────────────────────┘  │  │
│  │                  │                                      │  │
│  │  ┌───────────────▼──────────────────────────────────┐  │  │
│  │  │        MessagesContext (useReducer)              │  │  │
│  │  │  • Messages per conversation                     │  │  │
│  │  │  • Optimistic message sending                    │  │  │
│  │  │  • Message status (sending/sent/failed)          │  │  │
│  │  │  • Read receipts                                 │  │  │
│  │  │  • Pagination                                    │  │  │
│  │  └───────────────┬──────────────────────────────────┘  │  │
│  │                  │                                      │  │
│  │  ┌───────────────▼──────────────────────────────────┐  │  │
│  │  │       ConnectionContext (useReducer)             │  │  │
│  │  │  • WebSocket connection state                    │  │  │
│  │  │  • Reconnection attempts                         │  │  │
│  │  │  • Error handling                                │  │  │
│  │  │  • Connection status                             │  │  │
│  │  └──────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌────────────────────────────────────────────────────────┐  │
│  │          React Components (components/) [Phase 2]       │  │
│  │                                                         │  │
│  │  ┌──────────────────────────────────────────────────┐  │  │
│  │  │            <LiveChat /> (main)                   │  │  │
│  │  │  ┌────────────────┐  ┌──────────────────────┐   │  │  │
│  │  │  │ ConversationList│  │    ChatWindow        │   │  │  │
│  │  │  │                │  │  ┌────────────────┐  │   │  │  │
│  │  │  │ • List view    │  │  │  MessageList   │  │   │  │  │
│  │  │  │ • Search       │  │  │                │  │   │  │  │
│  │  │  │ • New button   │  │  └────────────────┘  │   │  │  │
│  │  │  │                │  │  ┌────────────────┐  │   │  │  │
│  │  │  └────────────────┘  │  │  MessageInput  │  │   │  │  │
│  │  │                      │  └────────────────┘  │   │  │  │
│  │  │                      └──────────────────────┘   │  │  │
│  │  └──────────────────────────────────────────────────┘  │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌────────────────────────────────────────────────────────┐  │
│  │                 Styles (styles/)                        │  │
│  │  • live-chat.css (BEM + CSS Variables)                 │  │
│  │  • Light/Dark theme tokens                             │  │
│  │  • Design system (colors, spacing, typography)         │  │
│  └─────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

## 📦 Module Dependencies

```
React Components (Phase 2)
    ↓ (uses)
Custom Hooks (Phase 2)
    ↓ (uses)
React Contexts
    ↓ (uses)
Core Libraries (ApiClient, WebSocketManager)
    ↓ (communicates with)
Laravel Backend
```

## 🔄 Data Flow

### 1. Loading Conversations
```
Component Mount
    ↓
useConversations() hook
    ↓
ConversationsContext dispatch(LOAD_CONVERSATIONS)
    ↓
ApiClient.getConversations()
    ↓
GET /api/conversations → Laravel
    ↓
Response ← Laravel
    ↓
dispatch(SET_CONVERSATIONS, data)
    ↓
Context state updated
    ↓
Components re-render
```

### 2. Sending a Message
```
User types & submits
    ↓
MessageInput component
    ↓
useMessages() hook
    ↓
dispatch(ADD_MESSAGE, optimistic)  ← Optimistic update
    ↓
ApiClient.sendMessage()
    ↓
POST /api/conversations/{id}/messages → Laravel
    ↓
Response ← Laravel
    ↓
dispatch(UPDATE_MESSAGE_STATUS, sent)
    ↓
WebSocket event .message.sent
    ↓
dispatch(RECEIVE_MESSAGE, confirmed)
    ↓
Components re-render
```

### 3. Real-time Updates
```
Laravel Event Broadcast
    ↓
Pusher/Redis
    ↓
Laravel Echo (browser)
    ↓
WebSocketManager event listener
    ↓
Context dispatch(RECEIVE_MESSAGE)
    ↓
Components re-render automatically
```

## 🗂️ File Organization

```
resources/js/
│
├── react/                          # Source code
│   │
│   ├── components/                # [Phase 2] React components
│   │   ├── LiveChat/
│   │   │   ├── LiveChat.tsx       # Main component
│   │   │   ├── LiveChat.stories.tsx
│   │   │   └── LiveChat.test.tsx
│   │   │
│   │   ├── ConversationList/
│   │   │   ├── ConversationList.tsx
│   │   │   ├── ConversationItem.tsx
│   │   │   └── ...
│   │   │
│   │   ├── ChatWindow/
│   │   │   ├── ChatWindow.tsx
│   │   │   ├── MessageList.tsx
│   │   │   ├── MessageItem.tsx
│   │   │   └── ...
│   │   │
│   │   └── shared/
│   │       ├── Avatar.tsx
│   │       ├── Button.tsx
│   │       └── ...
│   │
│   ├── contexts/                  # [✅] State management
│   │   ├── ConversationsContext.tsx
│   │   ├── MessagesContext.tsx
│   │   ├── ConnectionContext.tsx
│   │   ├── UIContext.tsx
│   │   └── index.ts
│   │
│   ├── hooks/                     # [Phase 2] Custom hooks
│   │   ├── useConversationsData.ts
│   │   ├── useMessagesData.ts
│   │   ├── useWebSocket.ts
│   │   ├── useTypingIndicator.ts
│   │   └── useOptimisticUpdate.ts
│   │
│   ├── lib/                       # [✅] Core libraries
│   │   ├── api-client.ts
│   │   ├── websocket-manager.ts
│   │   └── index.ts
│   │
│   ├── styles/                    # [✅] Styles
│   │   ├── live-chat.css
│   │   └── README.md
│   │
│   ├── types/                     # [✅] TypeScript types
│   │   └── index.ts
│   │
│   └── index.ts                   # [✅] Package entry
│
├── .storybook/                    # [✅] Storybook
│   ├── main.ts
│   └── preview.ts
│
├── package.json                   # [✅] Dependencies
├── tsconfig.json                  # [✅] TypeScript config
├── vite.config.ts                 # [✅] Build config
├── vitest.config.ts               # [✅] Test config
└── README.md                      # [✅] Documentation
```

## 🎨 CSS Architecture

### BEM Structure
```css
/* Block */
.lc-conversation { }

/* Element */
.lc-conversation__list { }
.lc-conversation__item { }
.lc-conversation__avatar { }
.lc-conversation__name { }

/* Modifier */
.lc-conversation__item--active { }
.lc-conversation__item--unread { }
```

### CSS Variables Hierarchy
```css
:root {
  /* Color palette */
  --lc-color-primary: #6366f1;
  --lc-color-background: #ffffff;
  --lc-color-text: #111827;
  
  /* Spacing scale */
  --lc-space-xs: 0.25rem;  /* 4px */
  --lc-space-sm: 0.5rem;   /* 8px */
  --lc-space-md: 1rem;     /* 16px */
  
  /* Typography */
  --lc-font-size-sm: 0.875rem;
  --lc-font-weight-medium: 500;
  
  /* Component-specific */
  --lc-sidebar-width: 20rem;
  --lc-avatar-size: 2.5rem;
}

[data-theme="dark"] {
  /* Dark mode overrides */
  --lc-color-background: #111827;
  --lc-color-text: #f9fafb;
}
```

## 🔌 API Integration

### REST Endpoints Used
```
GET    /api/conversations
POST   /api/conversations
GET    /api/conversations/{id}
DELETE /api/conversations/{id}
POST   /api/conversations/{id}/read

GET    /api/conversations/{id}/messages
POST   /api/conversations/{id}/messages
DELETE /api/conversations/{id}/messages/{messageId}
POST   /api/conversations/{id}/messages/{messageId}/read

GET    /api/users/search
GET    /api/user

POST   /api/conversations/{id}/typing
```

### WebSocket Events
```javascript
// Private user channel
Echo.private(`user.${userId}`)
    .listen('.message.sent', callback)
    .listen('.message.read', callback);

// Private conversation channel
Echo.private(`conversation.${conversationId}`)
    .listen('.message.sent', callback)
    .listen('.message.read', callback)
    .listen('.typing', callback);

// Presence channel
Echo.join('chat-users')
    .here(callback)
    .joining(callback)
    .leaving(callback);
```

## 🧪 Testing Strategy

### Unit Tests (Vitest)
```
✓ Context reducers
✓ API client methods
✓ WebSocket manager
✓ Utility functions
```

### Component Tests (React Testing Library)
```
✓ Component rendering
✓ User interactions
✓ State updates
✓ Event handling
```

### Integration Tests
```
✓ Context + API integration
✓ Component + Context integration
✓ WebSocket + Context integration
```

### Coverage Target
```
✓ Statements: 80%+
✓ Branches: 80%+
✓ Functions: 80%+
✓ Lines: 80%+
```

## 🚀 Build & Distribution

### Development
```bash
npm run dev           # Start Vite dev server
npm run storybook     # Start Storybook
npm test              # Run tests in watch mode
```

### Production
```bash
npm run build         # Build library (ESM)
npm run type-check    # TypeScript validation
npm test              # Run tests once
npm run lint          # ESLint check
```

### Distribution
```
Source files published (not pre-built)
  ↓
Developers build with their own Vite
  ↓
Tree-shaking removes unused code
  ↓
Optimized bundle for production
```

## 📊 Performance Considerations

### Optimization Strategies
1. **Split Contexts**: Prevent unnecessary re-renders
2. **Lazy Loading**: Components loaded on demand (Phase 2)
3. **Memo**: Expensive computations memoized
4. **Virtual Scrolling**: Large message lists (Phase 2)
5. **Debouncing**: Search and typing indicators
6. **Optimistic Updates**: Instant UI feedback

### Bundle Size Target
```
Library code:     < 50 KB (gzipped)
CSS:              < 10 KB (gzipped)
Total:            < 60 KB (gzipped)
```

## 🔒 Security

### CSRF Protection
```typescript
// API client includes CSRF token
headers: {
  'X-CSRF-TOKEN': csrfToken,
  'Content-Type': 'application/json',
}
```

### Authentication
```typescript
// Bearer token for authenticated requests
headers: {
  'Authorization': `Bearer ${token}`,
}
```

### Input Sanitization
```typescript
// Sanitized on Laravel backend
// Escaped in React rendering
```

## 🎯 Browser Support

### Target Browsers
- Chrome (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Edge (last 2 versions)

### Required Features
- ES2020+ (native)
- Fetch API (native)
- WebSocket (native)
- CSS Variables (native)
- ResizeObserver (native)

---

**Status**: Phase 1 Complete ✅  
**Next**: Phase 2 - Component Implementation
