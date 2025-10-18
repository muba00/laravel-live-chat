# LiveChat Component - Quick Start Guide

## Installation

```bash
# In your Laravel project
npm install @muba00/laravel-live-chat-react

# Peer dependencies (if not already installed)
npm install react react-dom laravel-echo pusher-js
```

## Basic Usage

```tsx
import { LiveChat } from "@muba00/laravel-live-chat-react";
// Import pre-compiled CSS (production-ready, no Tailwind needed)
import "@muba00/laravel-live-chat-react/dist/live-chat.css";

function App() {
    return <LiveChat userId={1} />;
}
```

## Full Configuration

```tsx
import { LiveChat } from "@muba00/laravel-live-chat-react";
// Import pre-compiled CSS (production-ready, no Tailwind needed)
import "@muba00/laravel-live-chat-react/dist/live-chat.css";

function App() {
    return (
        <LiveChat
            userId={1}
            apiUrl="/api/chat"
            wsHost="localhost"
            wsPort={6001}
            wsKey="your-pusher-key"
            theme="dark"
            height="600px"
            width="100%"
            className="my-custom-class"
            onError={(error) => console.error("Chat error:", error)}
        />
    );
}
```

## Props API

| Prop         | Type                        | Required | Default     | Description                   |
| ------------ | --------------------------- | -------- | ----------- | ----------------------------- |
| `userId`     | `number`                    | ✅ Yes   | -           | Current authenticated user ID |
| `apiUrl`     | `string`                    | No       | `/api/chat` | Base URL for chat API         |
| `wsHost`     | `string`                    | No       | -           | WebSocket server host         |
| `wsPort`     | `number`                    | No       | `6001`      | WebSocket server port         |
| `wsKey`      | `string`                    | No       | -           | Pusher/Reverb app key         |
| `wsForceTLS` | `boolean`                   | No       | `false`     | Force TLS for WebSocket       |
| `theme`      | `'light' \| 'dark'`         | No       | `'light'`   | Color theme                   |
| `height`     | `string`                    | No       | `'600px'`   | Component height              |
| `width`      | `string`                    | No       | `'100%'`    | Component width               |
| `className`  | `string`                    | No       | `''`        | Additional CSS class          |
| `onError`    | `(error: ApiError) => void` | No       | -           | Error callback                |

## Theming

### Using Dark Mode

```tsx
<LiveChat userId={1} theme="dark" />
```

### Custom Theme Colors

Override CSS variables in your stylesheet:

```css
.live-chat[data-theme="custom"] {
    --live-chat-primary: #ff6b6b;
    --live-chat-primary-hover: #ff5252;
    --live-chat-bg: #1a1a1a;
    --live-chat-text-primary: #ffffff;
}
```

```tsx
<LiveChat userId={1} theme="custom" />
```

### All Theme Variables

See `resources/js/react/styles/variables.css` for the complete list of 50+ CSS variables you can customize.

## CSS Compilation

The package ships with **pre-compiled, production-ready CSS**:

-   ✅ **Minified**: Optimized for production (9.6KB minified, ~2.4KB gzipped)
-   ✅ **Autoprefixed**: Works across all modern browsers
-   ✅ **No Tailwind required**: Completely independent, no build config needed
-   ✅ **Source maps included**: For debugging in development

### Using Pre-compiled CSS (Recommended)

```tsx
// Import the compiled CSS - works immediately, no build config needed
import "@muba00/laravel-live-chat-react/dist/live-chat.css";
```

### Using Source CSS (Advanced)

If you need to customize the CSS build process:

```tsx
// Import source CSS (requires PostCSS setup in your project)
import "@muba00/laravel-live-chat-react/styles";
// or import specific files:
import "@muba00/laravel-live-chat-react/styles/variables.css";
import "@muba00/laravel-live-chat-react/styles/live-chat.css";
```

## Responsive Behavior

The component is fully responsive:

-   **Mobile (< 768px)**: Slide-out sidebar, full-width chat
-   **Tablet (768px - 1023px)**: 280px sidebar, side-by-side layout
-   **Desktop (≥ 1024px)**: 320px sidebar, fixed layout

## Examples

### In a Laravel Blade View

```blade
<!-- resources/views/chat.blade.php -->
@extends('layouts.app')

@section('content')
<div id="chat-root"></div>
@endsection

@push('scripts')
<script type="module">
  import { createRoot } from 'react-dom/client';
  import { LiveChat } from '@muba00/laravel-live-chat-react';
  import '@muba00/laravel-live-chat-react/styles';

  const root = createRoot(document.getElementById('chat-root'));
  root.render(
    <LiveChat userId={{ auth()->id() }} />
  );
</script>
@endpush
```

### With React Router

```tsx
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { LiveChat } from "@muba00/laravel-live-chat-react";

function ChatPage() {
    const userId = useAuth(); // Your auth hook

    return (
        <div style={{ height: "100vh", padding: "1rem" }}>
            <LiveChat userId={userId} height="calc(100vh - 2rem)" />
        </div>
    );
}

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/chat" element={<ChatPage />} />
            </Routes>
        </BrowserRouter>
    );
}
```

### Full Page Chat

```tsx
function FullPageChat() {
    return (
        <div
            style={{
                position: "fixed",
                inset: 0,
                display: "flex",
            }}
        >
            <LiveChat userId={1} height="100vh" width="100%" />
        </div>
    );
}
```

### Chat Widget (Bottom Right)

```tsx
function ChatWidget() {
    const [open, setOpen] = useState(false);

    return (
        <>
            {/* Toggle Button */}
            <button
                onClick={() => setOpen(!open)}
                style={{
                    position: "fixed",
                    bottom: "1rem",
                    right: "1rem",
                    width: "60px",
                    height: "60px",
                    borderRadius: "50%",
                    background: "#3b82f6",
                    color: "white",
                    border: "none",
                    cursor: "pointer",
                    zIndex: 1000,
                }}
            >
                💬
            </button>

            {/* Chat Window */}
            {open && (
                <div
                    style={{
                        position: "fixed",
                        bottom: "5rem",
                        right: "1rem",
                        zIndex: 1000,
                        boxShadow: "0 10px 40px rgba(0,0,0,0.2)",
                    }}
                >
                    <LiveChat userId={1} height="500px" width="400px" />
                </div>
            )}
        </>
    );
}
```

## Features

### Implemented ✅

-   Real-time messaging
-   Typing indicators
-   Read receipts
-   Message grouping by sender & date
-   Auto-scroll to bottom
-   Load older messages (pagination)
-   Server-side search
-   Emoji picker (100+ emojis)
-   Light & dark themes
-   Fully responsive
-   Accessibility (WCAG 2.1 AA)
-   Optimistic UI
-   Link detection & auto-linking
-   XSS sanitization

### Coming Soon ⏳

-   File attachments
-   Browser notifications
-   Message reactions
-   Message editing/deletion
-   User presence (online/offline)

## Accessibility

The component is built with accessibility in mind:

-   ✅ Keyboard navigation
-   ✅ ARIA labels and roles
-   ✅ Focus management
-   ✅ Screen reader support
-   ✅ Reduced motion support
-   ✅ High contrast mode

### Keyboard Shortcuts

-   `Enter` - Send message
-   `Shift + Enter` - New line
-   `Tab` - Navigate between elements
-   `Escape` - Close modals/pickers

## Browser Support

-   Chrome/Edge (latest 2 versions)
-   Firefox (latest 2 versions)
-   Safari (latest 2 versions)
-   Modern mobile browsers

Requires ES2020+ features:

-   CSS Grid & Flexbox
-   CSS Variables
-   CSS Animations

## Troubleshooting

### Component not rendering

Make sure you've imported the styles:

```tsx
import "@muba00/laravel-live-chat-react/styles";
```

### WebSocket not connecting

Check your Laravel Echo configuration in `bootstrap.js`:

```js
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ["ws", "wss"],
});
```

### TypeScript errors

Make sure you have the necessary type declarations:

```bash
npm install --save-dev @types/react @types/react-dom
```

## API Documentation

See [API Reference](../../docs/api-reference.md) for detailed API documentation.

## License

MIT License - see [LICENSE.md](../../LICENSE.md)
