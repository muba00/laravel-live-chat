# Laravel Live Chat - Frontend Integration

This package provides multiple ways to integrate real-time chat functionality into your Laravel application's frontend.

## Quick Start

### 1. Publish Frontend Assets

```bash
php artisan vendor:publish --tag=live-chat-frontend
```

This publishes:

-   Blade components to `resources/views/vendor/live-chat`
-   JavaScript helpers to `resources/js/vendor/live-chat`
-   CSS styles to `resources/css/vendor/live-chat`

### 2. Include Styles

Add to your layout or app CSS:

```css
@import "/css/vendor/live-chat/live-chat.css";
```

### 3. Setup Laravel Echo

Install dependencies:

```bash
npm install --save-dev laravel-echo pusher-js
```

Initialize Echo in your `resources/js/bootstrap.js`:

```javascript
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});
```

## Integration Options

### Option 1: Blade Components (Simplest)

Use the built-in Blade components:

```blade
<x-live-chat::chat-window
    :conversation="$conversation"
    :currentUser="auth()->user()"
/>
```

**Pros:**

-   Zero configuration
-   Works out of the box
-   No build step needed

**Best for:** Simple projects, quick prototypes, server-rendered apps

### Option 2: Vanilla JavaScript

Use the framework-agnostic JavaScript client:

```javascript
import { LiveChatClient } from './vendor/live-chat/chat-client.js';

const client = new LiveChatClient({
    conversationId: {{ $conversation->id }},
    currentUserId: {{ auth()->id() }},
});

client.init();
```

**Pros:**

-   No framework dependency
-   Full control
-   Lightweight

**Best for:** Custom implementations, legacy projects, vanilla JS apps

### Option 3: Vue 3 (Composition API)

```vue
<template>
    <LiveChat
        :conversation-id="conversationId"
        :current-user-id="currentUserId"
        :initial-messages="messages"
    />
</template>

<script setup>
import LiveChat from "./components/LiveChat.vue";
// ... component logic
</script>
```

**Pros:**

-   Reactive and modern
-   TypeScript support
-   Composable patterns

**Best for:** Vue 3 applications, TypeScript projects

[Full Vue 3 Example →](./docs/examples/vue3-example.md)

### Option 4: React

```jsx
import LiveChat from "./components/LiveChat";

<LiveChat
    conversationId={conversationId}
    currentUserId={currentUserId}
    initialMessages={messages}
/>;
```

**Pros:**

-   Modern React patterns
-   Hooks-based
-   TypeScript ready

**Best for:** React applications, Inertia.js projects

[Full React Example →](./docs/examples/react-example.md)

### Option 5: Livewire + Alpine.js

```blade
@livewire('chat.chat-window', ['conversation' => $conversation])
```

**Pros:**

-   Full-stack Laravel
-   Minimal JavaScript
-   Easy to learn

**Best for:** Livewire applications, developers preferring backend

[Full Livewire Example →](./docs/examples/livewire-example.md)

## Features

### Real-Time Updates

-   ✅ Instant message delivery via WebSockets
-   ✅ Typing indicators
-   ✅ Read receipts
-   ✅ Online presence (coming soon)

### User Experience

-   ✅ Auto-scroll to new messages
-   ✅ Responsive design
-   ✅ Dark mode support
-   ✅ Mobile-friendly
-   ✅ Accessibility features

### Developer Experience

-   ✅ Multiple integration options
-   ✅ TypeScript support
-   ✅ Comprehensive documentation
-   ✅ Copy-paste examples
-   ✅ Customizable styling

## Customization

### Styling

Override CSS variables or classes:

```css
.live-chat-window {
    max-width: 800px;
    /* your custom styles */
}

.live-chat-message-sent .live-chat-message-content {
    background: #your-brand-color;
}
```

### JavaScript Behavior

Extend the `LiveChatClient` class:

```javascript
class CustomChatClient extends LiveChatClient {
    sendMessage(message) {
        // Add custom logic before sending
        console.log("Sending:", message);
        return super.sendMessage(message);
    }
}
```

### Component Props

Pass additional props to customize behavior:

```blade
<x-live-chat::chat-window
    :conversation="$conversation"
    :currentUser="auth()->user()"
    class="my-custom-class"
    placeholder="Your custom placeholder..."
/>
```

## API Endpoints

The frontend components expect these API endpoints (you need to implement these in Phase 4):

```
POST   /api/chat/conversations/{id}/messages
GET    /api/chat/conversations/{id}/messages
POST   /api/chat/conversations/{id}/mark-as-read
GET    /api/chat/conversations
```

## Environment Variables

Required for Laravel Echo/Reverb:

```env
VITE_REVERB_APP_KEY=your-app-key
VITE_REVERB_APP_ID=your-app-id
VITE_REVERB_HOST=your-host
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=https
```

## Troubleshooting

### Messages not appearing in real-time

1. Ensure Laravel Echo is initialized
2. Check WebSocket connection in browser console
3. Verify Reverb is running: `php artisan reverb:start`
4. Check broadcasting configuration

### Typing indicators not working

Typing indicators use client events (whispers):

-   Ensure Echo is properly configured
-   Check that the channel is subscribed
-   Verify the channel name matches configuration

### Styles not loading

-   Ensure CSS is published: `php artisan vendor:publish --tag=live-chat-css`
-   Import CSS in your main stylesheet
-   Check browser console for 404 errors

## Examples

See the [examples directory](./docs/examples/) for complete working examples:

-   [Vue 3 Example](./docs/examples/vue3-example.md)
-   [React Example](./docs/examples/react-example.md)
-   [Livewire Example](./docs/examples/livewire-example.md)

## Browser Support

-   Chrome 90+
-   Firefox 88+
-   Safari 14+
-   Edge 90+

## Performance

-   Optimized DOM updates
-   Efficient WebSocket usage
-   Lazy loading support
-   Minimal bundle size (~15KB gzipped for vanilla JS)

## Security

-   XSS protection via HTML escaping
-   CSRF token handling
-   Channel authorization
-   Private WebSocket channels

## Next Steps

1. **Implement API Layer** (Phase 4) - Backend endpoints for the frontend
2. **Add Tests** (Phase 10) - Test your frontend integration
3. **Customize** - Make it match your brand
4. **Extend** - Add features like file upload, reactions, etc.

## Need Help?

-   📚 [Full Documentation](../README.md)
-   💬 [GitHub Issues](https://github.com/your-repo/issues)
-   🎯 [Examples](./docs/examples/)

---

**Enjoy building your real-time chat! 🚀**
