# Livewire Chat Component Example

This example shows how to integrate Laravel Live Chat with Livewire and Alpine.js.

## Installation

```bash
composer require livewire/livewire
npm install --save-dev laravel-echo pusher-js alpinejs
```

## Setup

Publish Livewire assets:

```bash
php artisan livewire:publish --assets
```

## Chat Component

Create a Livewire component:

```bash
php artisan make:livewire Chat/ChatWindow
```

Update `app/Livewire/Chat/ChatWindow.php`:

```php
<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Livewire\Attributes\On;
use muba00\LaravelLiveChat\Facades\LaravelLiveChat;
use muba00\LaravelLiveChat\Models\Conversation;

class ChatWindow extends Component
{
    public Conversation $conversation;
    public $messages = [];
    public $newMessage = '';
    public $otherUser;

    public function mount(Conversation $conversation)
    {
        $this->conversation = $conversation;
        $this->otherUser = $conversation->getOtherUser(auth()->user());
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $this->messages = LaravelLiveChat::getLatestMessages($this->conversation, 50);
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|max:5000',
        ]);

        $message = LaravelLiveChat::sendMessage(
            $this->conversation,
            auth()->user(),
            $this->newMessage
        );

        // Add to messages array
        $this->messages->push($message);
        $this->newMessage = '';

        // Broadcast event
        $this->dispatch('message-sent', messageId: $message->id);
    }

    #[On('echo-private:chat.{conversation.id},.MessageSent')]
    public function handleMessageSent($event)
    {
        // Reload messages when new message arrives
        if ($event['sender_id'] !== auth()->id()) {
            $this->loadMessages();
            $this->dispatch('message-received', messageId: $event['id']);
        }
    }

    public function markAsRead()
    {
        LaravelLiveChat::markConversationAsRead($this->conversation, auth()->user());
    }

    public function render()
    {
        return view('livewire.chat.chat-window');
    }
}
```

Create the view `resources/views/livewire/chat/chat-window.blade.php`:

```blade
<div
    x-data="{
        isTyping: false,
        typingTimeout: null,
        handleTyping() {
            clearTimeout(this.typingTimeout);

            if (window.Echo) {
                window.Echo.private('{{ config('live-chat.broadcasting.channel_prefix', 'chat') }}.{{ $conversation->id }}')
                    .whisper('typing', {
                        user_id: {{ auth()->id() }},
                        is_typing: true
                    });
            }

            this.typingTimeout = setTimeout(() => {
                if (window.Echo) {
                    window.Echo.private('{{ config('live-chat.broadcasting.channel_prefix', 'chat') }}.{{ $conversation->id }}')
                        .whisper('typing', {
                            user_id: {{ auth()->id() }},
                            is_typing: false
                        });
                }
            }, 1000);
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messages;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        }
    }"
    x-init="
        scrollToBottom();

        // Listen for client typing events
        if (window.Echo) {
            window.Echo.private('{{ config('live-chat.broadcasting.channel_prefix', 'chat') }}.{{ $conversation->id }}')
                .listenForWhisper('typing', (e) => {
                    if (e.user_id !== {{ auth()->id() }}) {
                        isTyping = e.is_typing;
                        if (e.is_typing) {
                            setTimeout(() => isTyping = false, 3000);
                        }
                    }
                });
        }

        // Scroll on new messages
        $watch('$wire.messages', () => scrollToBottom());
    "
    class="live-chat-window"
    wire:poll.5s="loadMessages"
>
    <!-- Header -->
    <div class="live-chat-header">
        <h3 class="live-chat-title">
            Chat with {{ $otherUser->name ?? 'User' }}
        </h3>
    </div>

    <!-- Messages -->
    <div class="live-chat-body">
        <div x-ref="messages" class="live-chat-messages">
            @forelse($messages as $message)
                <div class="live-chat-message {{ $message->sender_id === auth()->id() ? 'live-chat-message-sent' : 'live-chat-message-received' }}"
                     wire:key="message-{{ $message->id }}">
                    <div class="live-chat-message-content">
                        <div class="live-chat-message-text">
                            {{ $message->message }}
                        </div>
                        <div class="live-chat-message-meta">
                            <span class="live-chat-message-time">
                                {{ $message->created_at->diffForHumans() }}
                            </span>
                            @if($message->sender_id === auth()->id() && $message->isRead())
                                <span class="live-chat-message-status">✓✓</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="live-chat-no-messages">
                    <p>No messages yet</p>
                </div>
            @endforelse
        </div>

        <!-- Typing Indicator -->
        <div x-show="isTyping"
             x-transition
             class="live-chat-typing-indicator">
            <span class="live-chat-typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </span>
            <span class="live-chat-typing-text">typing...</span>
        </div>
    </div>

    <!-- Input Form -->
    <div class="live-chat-footer">
        <form wire:submit="sendMessage" class="live-chat-form">
            <div class="live-chat-input-wrapper">
                <textarea
                    wire:model.live="newMessage"
                    x-on:input="handleTyping"
                    x-on:keydown.enter.exact.prevent="$wire.sendMessage()"
                    x-ref="input"
                    x-init="
                        $watch('$wire.newMessage', () => {
                            $refs.input.style.height = 'auto';
                            $refs.input.style.height = $refs.input.scrollHeight + 'px';
                        })
                    "
                    class="live-chat-input"
                    placeholder="Type a message..."
                    rows="1"
                    required
                ></textarea>

                <button
                    type="submit"
                    class="live-chat-send-button"
                    wire:loading.attr="disabled"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Loading Indicator -->
    <div wire:loading class="live-chat-loading">
        Sending...
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="/css/vendor/live-chat/live-chat.css">
    <style>
        .live-chat-loading {
            position: absolute;
            bottom: 5rem;
            right: 1rem;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            z-index: 10;
        }
    </style>
@endpush
```

## Setup Laravel Echo

In your layout file or `resources/js/app.js`:

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

## Usage in Blade

```blade
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @livewireStyles
</head>
<body>
    <div class="container mx-auto py-8">
        @livewire('chat.chat-window', ['conversation' => $conversation])
    </div>

    @livewireScripts
</body>
</html>
```

## Conversation List Component

Create a conversation list component:

```bash
php artisan make:livewire Chat/ConversationList
```

`app/Livewire/Chat/ConversationList.php`:

```php
<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use muba00\LaravelLiveChat\Facades\LaravelLiveChat;

class ConversationList extends Component
{
    public $conversations;
    public $selectedConversationId = null;

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $this->conversations = LaravelLiveChat::getUserConversations(auth()->user());
    }

    public function selectConversation($conversationId)
    {
        $this->selectedConversationId = $conversationId;
        $this->dispatch('conversation-selected', conversationId: $conversationId);
    }

    public function getUnreadCount($conversation)
    {
        return LaravelLiveChat::getUnreadCount($conversation, auth()->user());
    }

    public function render()
    {
        return view('livewire.chat.conversation-list');
    }
}
```

`resources/views/livewire/chat/conversation-list.blade.php`:

```blade
<div class="conversation-list">
    <h2 class="text-xl font-bold mb-4">Messages</h2>

    <div class="space-y-2">
        @forelse($conversations as $conversation)
            @php
                $otherUser = $conversation->getOtherUser(auth()->user());
                $unreadCount = $this->getUnreadCount($conversation);
            @endphp

            <button
                wire:click="selectConversation({{ $conversation->id }})"
                class="w-full text-left p-4 rounded-lg transition {{ $selectedConversationId === $conversation->id ? 'bg-blue-50 border-blue-500' : 'bg-white hover:bg-gray-50' }} border"
            >
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h3 class="font-semibold">{{ $otherUser->name }}</h3>
                        <p class="text-sm text-gray-600 truncate">
                            {{ $conversation->messages()->latest()->first()?->message ?? 'No messages yet' }}
                        </p>
                    </div>

                    @if($unreadCount > 0)
                        <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </div>

                <div class="text-xs text-gray-500 mt-1">
                    {{ $conversation->last_message_at?->diffForHumans() ?? 'Just now' }}
                </div>
            </button>
        @empty
            <p class="text-gray-500 text-center py-8">No conversations yet</p>
        @endforelse
    </div>
</div>
```

## Full Page Layout

Create a full chat page layout:

```blade
<!-- resources/views/chat/index.blade.php -->
<x-app-layout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Conversation List -->
            <div class="md:col-span-1">
                @livewire('chat.conversation-list')
            </div>

            <!-- Chat Window -->
            <div class="md:col-span-2">
                @if($conversation)
                    @livewire('chat.chat-window', ['conversation' => $conversation])
                @else
                    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                        Select a conversation to start chatting
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
```

## Advanced Features

### Auto-scroll on new messages

Already included with Alpine.js `$watch` directive.

### Real-time updates without polling

Update the component to use pure Echo events:

```php
// Remove wire:poll from the component
// Echo events will handle real-time updates automatically
```

### Mark as read when viewing

Add to the component's `mount()` method:

```php
public function mount(Conversation $conversation)
{
    $this->conversation = $conversation;
    $this->otherUser = $conversation->getOtherUser(auth()->user());
    $this->loadMessages();
    $this->markAsRead(); // Mark as read on load
}
```

### Notifications

Create a notification component for new messages in other conversations:

```bash
php artisan make:livewire Chat/Notifications
```

## Performance Tips

1. **Lazy loading**: Use `wire:init` for initial load
2. **Debounce typing**: Already implemented with timeout
3. **Limit messages**: Paginate old messages
4. **Cache conversations**: Cache user's conversation list

## Testing

```php
use Livewire\Livewire;

test('can send message', function () {
    $conversation = Conversation::factory()->create();

    Livewire::actingAs($conversation->user1)
        ->test(ChatWindow::class, ['conversation' => $conversation])
        ->set('newMessage', 'Hello World')
        ->call('sendMessage')
        ->assertSet('newMessage', '')
        ->assertDispatched('message-sent');
});
```

## Next Steps

-   Add file upload with Livewire
-   Implement message search
-   Add user presence indicators
-   Create admin chat dashboard
