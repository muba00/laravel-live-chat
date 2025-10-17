# Laravel Live Chat

[![Latest Version on Packagist](https://img.shields.io/packagist/v/muba00/laravel-live-chat.svg?style=flat-square)](https://packagist.org/packages/muba00/laravel-live-chat)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/muba00/laravel-live-chat/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/muba00/laravel-live-chat/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/muba00/laravel-live-chat/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/muba00/laravel-live-chat/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/muba00/laravel-live-chat.svg?style=flat-square)](https://packagist.org/packages/muba00/laravel-live-chat)

A simple, elegant Laravel package that adds **real-time 1-to-1 chat** functionality to your application using **Laravel Reverb**. Easy to install, easy to use, and follows Laravel best practices.

## ✨ Features

-   🚀 **Real-time messaging** using Laravel Reverb WebSockets
-   💬 **1-to-1 conversations** between users
-   ✅ **Read receipts** to track message status
-   ✍️ **Typing indicators** for better UX
-   🔒 **Built-in authorization** policies
-   ️ **Highly configurable** with sensible defaults
-   🧪 **Thoroughly tested** with 100+ tests
-   📦 **Easy to remove** - clean uninstall with no residual code

> **🎨 Frontend Components Coming Soon!** Complete React, Vue 3, and Livewire UI components are currently in development. For now, you can use the backend API to build your own custom frontend.

## 📋 Requirements

-   PHP 8.3+
-   Laravel 11.0+
-   Laravel Reverb (for real-time features)
-   Laravel Sanctum (for API authentication)

## 🚀 Quick Start

### 1. Install the Package

```bash
composer require muba00/laravel-live-chat
```

### 2. Run the Installer

```bash
php artisan live-chat:install
```

This interactive command will:

-   Check prerequisites
-   Publish configuration files
-   Publish and run migrations
-   Guide you through additional setup steps

### 3. Configure Broadcasting

Install Laravel Reverb:

```bash
composer require laravel/reverb
php artisan reverb:install
```

### 4. Configure Authentication

If not already done, install Laravel Sanctum:

```bash
composer require laravel/sanctum
php artisan install:api
```

### 5. Install Frontend Dependencies

```bash
npm install --save-dev laravel-echo pusher-js
npm run build
```

### 6. Start Using the Chat!

**Backend - Create a conversation and send a message:**

```php
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

// Get or create a conversation between two users
$conversation = Conversation::between(auth()->user(), $otherUser);

// Send a message
$message = Message::create([
    'conversation_id' => $conversation->id,
    'sender_id' => auth()->id(),
    'message' => 'Hello! How are you?',
]);
```

**Frontend - Build your own custom UI:**

Use the provided API endpoints to build your custom chat interface. Complete frontend components for React, Vue 3, and Livewire are coming soon!

## 📖 Documentation

### Installation

#### Manual Installation (Alternative)

If you prefer to install manually instead of using the installer command:

```bash
# Install package
composer require muba00/laravel-live-chat

# Publish config
php artisan vendor:publish --tag="laravel-live-chat-config"

# Publish migrations
php artisan vendor:publish --tag="laravel-live-chat-migrations"

# Run migrations
php artisan migrate
```

### Configuration

The package configuration file is published to `config/live-chat.php`. Here are the key configuration options:

```php
return [
    // User model configuration
    'user_model' => 'App\\Models\\User',

    // Customize table names
    'tables' => [
        'conversations' => 'live_chat_conversations',
        'messages' => 'live_chat_messages',
    ],

    // API routes configuration
    'routes' => [
        'enabled' => true,
        'prefix' => 'chat/api',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    // Broadcasting settings
    'broadcasting' => [
        'enabled' => true,
        'channel_prefix' => 'chat',
    ],

    // Pagination
    'pagination' => [
        'messages_per_page' => 50,
        'conversations_per_page' => 20,
    ],

    // Message retention and storage
    'storage' => [
        'retention_days' => null, // null = keep forever
        'archive_enabled' => false,
        'max_message_length' => 5000,
    ],

    // Feature toggles
    'features' => [
        'read_receipts' => true,
        'typing_indicators' => true,
    ],

    // Security settings
    'security' => [
        'sanitize_messages' => true,
        'max_conversations_per_user' => 100,
    ],
];
```

#### Environment Variables

You can override configuration using environment variables:

```env
CHAT_USER_MODEL="App\Models\User"
CHAT_MESSAGES_PER_PAGE=50
CHAT_CONVERSATIONS_PER_PAGE=20
CHAT_RETENTION_DAYS=90
CHAT_CACHE_ENABLED=true
CHAT_CACHE_TTL=3600
```

### Usage

#### Backend API

The package automatically registers RESTful API routes:

```
POST   /chat/api/conversations                           # Create/get conversation
GET    /chat/api/conversations                           # List user's conversations
GET    /chat/api/conversations/{id}                      # Get conversation details
DELETE /chat/api/conversations/{id}                      # Delete conversation

GET    /chat/api/conversations/{id}/messages             # Get messages
POST   /chat/api/conversations/{id}/messages             # Send message
POST   /chat/api/conversations/{id}/messages/mark-read   # Mark messages as read
GET    /chat/api/conversations/{id}/messages/unread-count # Get unread count

POST   /chat/api/conversations/{id}/typing               # Broadcast typing indicator
```

#### Using Models

```php
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

// Get or create a conversation
$conversation = Conversation::between($user1, $user2);

// Check if a user is part of a conversation
$conversation->includesUser($user);

// Get the other user in a conversation
$otherUser = $conversation->getOtherUser(auth()->user());

// Send a message
$message = Message::create([
    'conversation_id' => $conversation->id,
    'sender_id' => auth()->id(),
    'message' => 'Hello!',
]);

// Mark messages as read
$conversation->markAsReadBy(auth()->user());

// Get unread message count
$unreadCount = $conversation->unreadMessagesFor(auth()->user())->count();
```

#### Frontend Integration

The package provides a RESTful API for building custom chat interfaces. Complete frontend components for React, Vue 3, and Livewire are currently in development.

##### Example API Usage

```javascript
// Fetch conversations
const response = await fetch('/chat/api/conversations', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    }
});
const conversations = await response.json();

// Send a message
await fetch(`/chat/api/conversations/${conversationId}/messages`, {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({ message: 'Hello!' })
});

// Listen for real-time messages
Echo.private(`chat.${conversationId}`)
    .listen('MessageSent', (event) => {
        console.log('New message:', event.message);
    });
```

### Broadcasting Setup

#### Configure Laravel Reverb

Add to your `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### Setup Laravel Echo

In your `resources/js/bootstrap.js`:

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

#### Start Reverb Server

```bash
php artisan reverb:start
```

For production, use a process manager like Supervisor.

### Artisan Commands

#### Install Command

```bash
php artisan live-chat:install [options]

Options:
  --force            Overwrite existing files
  --skip-migrations  Skip publishing migrations
  --skip-config      Skip publishing config
```

#### Cleanup Command

```bash
php artisan live-chat:cleanup [options]

Options:
  --days=X          Number of days to retain (overrides config)
  --archive         Archive messages instead of deleting
  --dry-run         Show what would be deleted without deleting
  --force           Skip confirmation prompt
```

**Schedule cleanup automatically:**

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Clean up messages older than 90 days, daily at 2 AM
    $schedule->command('live-chat:cleanup --days=90 --force')
        ->dailyAt('02:00');
}
```

### Security

The package includes built-in authorization:

-   Users can only access conversations they're part of
-   Messages are validated before storage
-   XSS protection via message sanitization
-   Rate limiting support
-   Private broadcast channels with authorization

### Customization

#### Customize Models

You can extend the package models in your application:

```php
namespace App\Models;

use muba00\LaravelLiveChat\Models\Conversation as BaseConversation;

class Conversation extends BaseConversation
{
    // Add your custom methods and properties
}
```

Then update your config to use your custom model.

#### Disable Auto-registered Routes

```php
// In config/live-chat.php
'routes' => [
    'enabled' => false,
],
```

Then register routes manually in your `routes/api.php`.

## 🧪 Testing

```bash
composer test
composer test-coverage
composer analyse
composer format
```

## 🗑️ Uninstalling

```bash
# Rollback migrations
php artisan migrate:rollback

# Remove published assets (optional)
rm config/live-chat.php

# Remove package
composer remove muba00/laravel-live-chat
```

## 📚 Additional Resources

-   [API Reference](docs/api-reference.md)

## 🐛 Troubleshooting

### Messages not appearing in real-time

1. Check Reverb is running: `php artisan reverb:start`
2. Verify `.env` configuration for Reverb
3. Check browser console for WebSocket errors
4. Ensure Laravel Echo is properly initialized

### Authorization errors

1. Verify Sanctum is installed and configured
2. Check API token is being sent in requests
3. Ensure user is part of the conversation

### Database errors

1. Run migrations: `php artisan migrate`
2. Check table names in config match your setup
3. Verify user model configuration

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 👏 Credits

-   [Mubariz Hajimuradov](https://github.com/muba00)
-   [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
