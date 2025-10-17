# Complete Implementation Example

This guide shows a complete, working implementation of Laravel Live Chat in a Laravel application.

## Prerequisites

-   Fresh Laravel 11+ installation
-   PHP 8.3+
-   Node.js and npm

## Step 1: Install Required Packages

```bash
# Install the chat package
composer require muba00/laravel-live-chat

# Install Laravel Reverb
composer require laravel/reverb

# Install Laravel Sanctum (if not already installed)
composer require laravel/sanctum
```

## Step 2: Run Installers

```bash
# Install Live Chat
php artisan live-chat:install

# Install Reverb
php artisan reverb:install

# Install Sanctum API
php artisan install:api
```

## Step 3: Configure Environment

Add to your `.env`:

```env
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb Configuration
REVERB_APP_ID=123456
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite Configuration (for frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Step 4: Create Chat Controller

Create `app/Http/Controllers/ChatController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use muba00\LaravelLiveChat\Models\Conversation;

class ChatController extends Controller
{
    /**
     * Show the chat inbox with all conversations.
     */
    public function index()
    {
        $conversations = Conversation::where('user1_id', auth()->id())
            ->orWhere('user2_id', auth()->id())
            ->with(['user1', 'user2', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return view('chat.index', compact('conversations'));
    }

    /**
     * Show a specific conversation.
     */
    public function show($userId)
    {
        $otherUser = User::findOrFail($userId);

        // Get or create conversation
        $conversation = Conversation::between(auth()->user(), $otherUser);

        // Mark messages as read
        $conversation->markAsReadBy(auth()->user());

        return view('chat.show', compact('conversation', 'otherUser'));
    }

    /**
     * Show list of users to start a conversation with.
     */
    public function users()
    {
        $users = User::where('id', '!=', auth()->id())
            ->orderBy('name')
            ->paginate(20);

        return view('chat.users', compact('users'));
    }
}
```

## Step 5: Add Routes

In `routes/web.php`:

```php
<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/users', [ChatController::class, 'users'])->name('chat.users');
    Route::get('/chat/{userId}', [ChatController::class, 'show'])->name('chat.show');
});

require __DIR__.'/auth.php';
```

## Step 6: Create Views

### Chat Inbox (`resources/views/chat/index.blade.php`)

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Chat') }}
            </h2>
            <a href="{{ route('chat.users') }}"
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                New Chat
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Conversations</h3>

                    @forelse($conversations as $conversation)
                        @php
                            $otherUser = $conversation->getOtherUser(auth()->user());
                            $unreadCount = $conversation->unreadMessagesFor(auth()->user())->count();
                        @endphp

                        <a href="{{ route('chat.show', $otherUser->id) }}"
                           class="block p-4 border-b hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                        {{ substr($otherUser->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold">{{ $otherUser->name }}</div>
                                        @if($conversation->lastMessage)
                                            <div class="text-sm text-gray-600">
                                                {{ Str::limit($conversation->lastMessage->message, 50) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    @if($conversation->last_message_at)
                                        <div class="text-xs text-gray-500">
                                            {{ $conversation->last_message_at->diffForHumans() }}
                                        </div>
                                    @endif
                                    @if($unreadCount > 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 rounded-full mt-1">
                                            {{ $unreadCount }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-gray-500 text-center py-8">
                            No conversations yet.
                            <a href="{{ route('chat.users') }}" class="text-blue-500 hover:underline">
                                Start a new chat
                            </a>
                        </p>
                    @endforelse

                    <div class="mt-4">
                        {{ $conversations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### Chat Window (`resources/views/chat/show.blade.php`)

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('chat.index') }}"
               class="text-gray-600 hover:text-gray-900">
                ← Back
            </a>
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                {{ substr($otherUser->name, 0, 1) }}
            </div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $otherUser->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                {{-- Use the package's Blade component --}}
                <x-live-chat::chat-window
                    :conversation="$conversation"
                    :currentUser="auth()->user()"
                    class="h-[600px]"
                />
            </div>
        </div>
    </div>

    {{-- Include CSS --}}
    <link rel="stylesheet" href="{{ asset('css/vendor/live-chat/live-chat.css') }}">
</x-app-layout>
```

### User List (`resources/views/chat/users.blade.php`)

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('chat.index') }}"
               class="text-gray-600 hover:text-gray-900">
                ← Back
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Start New Chat') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Select a user to chat with</h3>

                    @foreach($users as $user)
                        <a href="{{ route('chat.show', $user->id) }}"
                           class="block p-4 border-b hover:bg-gray-50 transition">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-semibold">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-600">{{ $user->email }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

## Step 7: Setup Frontend

### Install Dependencies

```bash
npm install --save-dev laravel-echo pusher-js
```

### Configure Laravel Echo

In `resources/js/bootstrap.js`, add:

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
    authEndpoint: "/broadcasting/auth",
    auth: {
        headers: {
            Authorization: `Bearer ${
                document.querySelector('meta[name="csrf-token"]')?.content
            }`,
        },
    },
});
```

### Build Assets

```bash
npm run build
```

For development:

```bash
npm run dev
```

## Step 8: Update Layout

Add meta tag in your `resources/views/layouts/app.blade.php` or main layout:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Reverb Configuration --}}
    <meta name="reverb-app-key" content="{{ config('reverb.apps.0.key') }}">
    <meta name="reverb-host" content="{{ config('reverb.apps.0.host') }}">
    <meta name="reverb-port" content="{{ config('reverb.apps.0.port') }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
```

## Step 9: Create Seeders (Optional)

Create a seeder to test with multiple users:

```bash
php artisan make:seeder ChatDemoSeeder
```

In `database/seeders/ChatDemoSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Models\Message;

class ChatDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        $alice = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            ['name' => 'Alice Smith', 'password' => bcrypt('password')]
        );

        $bob = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            ['name' => 'Bob Johnson', 'password' => bcrypt('password')]
        );

        $charlie = User::firstOrCreate(
            ['email' => 'charlie@example.com'],
            ['name' => 'Charlie Brown', 'password' => bcrypt('password')]
        );

        // Create conversations with messages
        $conversation1 = Conversation::between($alice, $bob);

        Message::create([
            'conversation_id' => $conversation1->id,
            'sender_id' => $alice->id,
            'message' => 'Hi Bob! How are you?',
        ]);

        Message::create([
            'conversation_id' => $conversation1->id,
            'sender_id' => $bob->id,
            'message' => 'Hey Alice! I\'m doing great, thanks for asking!',
        ]);

        $conversation2 = Conversation::between($alice, $charlie);

        Message::create([
            'conversation_id' => $conversation2->id,
            'sender_id' => $alice->id,
            'message' => 'Hey Charlie, want to grab lunch?',
        ]);

        $this->command->info('Demo data created successfully!');
        $this->command->info('Users created:');
        $this->command->info('- alice@example.com / password');
        $this->command->info('- bob@example.com / password');
        $this->command->info('- charlie@example.com / password');
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=ChatDemoSeeder
```

## Step 10: Start Services

Open multiple terminal windows:

**Terminal 1 - Laravel Server:**

```bash
php artisan serve
```

**Terminal 2 - Reverb WebSocket Server:**

```bash
php artisan reverb:start
```

**Terminal 3 - Vite Dev Server (if developing):**

```bash
npm run dev
```

**Terminal 4 - Queue Worker (if using queued broadcasts):**

```bash
php artisan queue:work
```

## Step 11: Test the Application

1. Visit `http://localhost:8000`
2. Register or log in with one of the seeded users
3. Navigate to `/chat`
4. Start a conversation
5. Open a second browser/incognito window
6. Log in as a different user
7. Send messages back and forth
8. Watch them appear in real-time! ✨

## Production Deployment

### Supervisor Configuration

Create `/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
command=php /path/to/your/app/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/reverb.log
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

### SSL/TLS Configuration

For production, use WSS (secure WebSocket):

```env
REVERB_SCHEME=https
REVERB_PORT=443
```

Configure your web server (Nginx/Apache) to proxy WebSocket connections.

## API Usage Example

If you want to use the API directly (e.g., for mobile apps):

```javascript
// Get all conversations
fetch("/chat/api/conversations", {
    headers: {
        Authorization: "Bearer YOUR_SANCTUM_TOKEN",
        Accept: "application/json",
    },
})
    .then((response) => response.json())
    .then((data) => console.log(data));

// Send a message
fetch("/chat/api/conversations/1/messages", {
    method: "POST",
    headers: {
        Authorization: "Bearer YOUR_SANCTUM_TOKEN",
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    body: JSON.stringify({
        message: "Hello from API!",
    }),
})
    .then((response) => response.json())
    .then((data) => console.log(data));
```

## Troubleshooting

### WebSocket Connection Issues

1. Check Reverb is running: `php artisan reverb:start --debug`
2. Verify environment variables match in `.env` and `vite.config.js`
3. Check browser console for connection errors
4. Ensure firewall allows the WebSocket port

### Messages Not Saving

1. Check database connection
2. Verify migrations ran successfully
3. Check Laravel logs: `storage/logs/laravel.log`

### Authentication Errors

1. Ensure Sanctum is properly configured
2. Check CSRF token is being sent
3. Verify middleware is applied to routes

## Next Steps

-   Customize the UI to match your design
-   Add file/image attachments
-   Implement group chats
-   Add emoji support
-   Create mobile app using the API

For more examples and documentation, visit the [package repository](https://github.com/muba00/laravel-live-chat).
