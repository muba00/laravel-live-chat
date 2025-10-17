# Laravel Live Chat - Examples

This directory contains comprehensive examples of how to integrate and use the Laravel Live Chat package in your application.

## Available Examples

### 📘 [Complete Implementation](complete-implementation.md)

**⭐ Start here for a full working example!**

A complete, step-by-step guide showing:

-   Full Laravel application setup with authentication
-   Controllers, routes, and views
-   Frontend configuration with Laravel Echo
-   Testing with multiple users
-   Production deployment tips

Perfect for: Getting started, understanding the full flow, production setup

---

### 🎨 Frontend Framework Examples

#### [Vue 3 Example](vue3-example.md)

-   Composition API with TypeScript
-   Reactive state management
-   Custom event handling
-   Full source code included

#### [React Example](react-example.md)

-   Functional components with hooks
-   State management patterns
-   Real-time updates
-   TypeScript support

#### [Livewire Example](livewire-example.md)

-   Alpine.js integration
-   Server-side rendering
-   Real-time with Laravel Echo
-   Minimal JavaScript

## Choosing the Right Example

| Use Case           | Recommended Example                                          |
| ------------------ | ------------------------------------------------------------ |
| New project setup  | [Complete Implementation](complete-implementation.md)        |
| Vue.js project     | [Vue 3 Example](vue3-example.md)                             |
| React project      | [React Example](react-example.md)                            |
| Laravel + Livewire | [Livewire Example](livewire-example.md)                      |
| No framework       | Use package's Blade components (see Complete Implementation) |

## Quick Start

1. **Start with the [Complete Implementation](complete-implementation.md)** to understand the full setup
2. **Choose your frontend framework** and follow that specific guide
3. **Customize** the components to match your design
4. **Deploy** using the production tips in the complete example

## Common Integration Patterns

### Pattern 1: Using Package Blade Components

The simplest approach - use the included Blade components:

```blade
<x-live-chat::chat-window
    :conversation="$conversation"
    :currentUser="auth()->user()"
/>
```

### Pattern 2: Custom Frontend

Build your own UI using the package's API:

```javascript
// Your custom JavaScript
fetch("/chat/api/conversations/1/messages")
    .then((response) => response.json())
    .then((messages) => displayMessages(messages));
```

### Pattern 3: Hybrid Approach

Combine Blade components for structure, custom JS for behavior:

```blade
<div id="chat-container">
    {{-- Use package API, custom rendering --}}
</div>

<script>
    // Custom JavaScript using the API
    const client = new LiveChatClient({ ... });
</script>
```

## Need Help?

-   Check the [API Reference](../api-reference.md)
-   Read the [Frontend Integration Guide](../frontend-integration.md)
-   Review the [main README](../../README.md)

## Contributing Examples

Have a great example? We'd love to see it! Submit a PR with:

-   Complete, working code
-   Clear instructions
-   Screenshots or GIFs
-   Common pitfalls section
