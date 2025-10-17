<?php

// config for muba00/LaravelLiveChat
return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model that represents users in your application.
    | This should be the fully qualified class name of your User model.
    |
    */
    'user_model' => env('CHAT_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the package.
    | Useful if you need to avoid naming conflicts or follow specific conventions.
    |
    */
    'tables' => [
        'conversations' => 'live_chat_conversations',
        'messages' => 'live_chat_messages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes that will be registered by the package.
    | Set 'enabled' to false if you want to register routes manually.
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'chat/api',
        'middleware' => ['api', 'auth:sanctum'],
        'name' => 'live-chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the broadcasting behavior for real-time features.
    | Requires Laravel Reverb or another compatible broadcast driver.
    |
    */
    'broadcasting' => [
        'enabled' => true,
        'channel_prefix' => 'chat',
        'connection' => env('CHAT_BROADCAST_CONNECTION', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Configure pagination for messages and conversations.
    | Higher values may impact performance with large conversation history.
    |
    */
    'pagination' => [
        'messages_per_page' => env('CHAT_MESSAGES_PER_PAGE', 50),
        'conversations_per_page' => env('CHAT_CONVERSATIONS_PER_PAGE', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Storage & Retention
    |--------------------------------------------------------------------------
    |
    | Configure how messages are stored and retained.
    |
    */
    'storage' => [
        // Automatically delete messages older than X days (null = never delete)
        'retention_days' => env('CHAT_RETENTION_DAYS', null),

        // Archive old messages instead of deleting them
        'archive_enabled' => false,

        // Path to store archived messages (if archiving is enabled)
        'archive_path' => storage_path('app/chat-archives'),

        // Maximum message length in characters
        'max_message_length' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for improved performance.
    | Useful for conversation counts, unread message counts, etc.
    |
    */
    'cache' => [
        'enabled' => env('CHAT_CACHE_ENABLED', true),
        'ttl' => env('CHAT_CACHE_TTL', 3600), // seconds
        'prefix' => 'live_chat',
        'driver' => env('CHAT_CACHE_DRIVER', null), // null = default cache driver
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable optional features provided by the package.
    |
    */
    'features' => [
        'read_receipts' => true,
        'typing_indicators' => true,
        'message_editing' => false,
        'message_deletion' => false,
        'conversation_archiving' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API endpoints to prevent abuse.
    | Uses Laravel's rate limiting features.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'messages_per_minute' => env('CHAT_RATE_LIMIT_MESSAGES', 60),
        'conversations_per_minute' => env('CHAT_RATE_LIMIT_CONVERSATIONS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events & Listeners
    |--------------------------------------------------------------------------
    |
    | Configure event dispatching behavior.
    |
    */
    'events' => [
        'dispatch_model_events' => true, // Fire Eloquent model events
        'queue_broadcasts' => env('CHAT_QUEUE_BROADCASTS', false), // Queue broadcast events
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security-related configuration options.
    |
    */
    'security' => [
        // Sanitize message content to prevent XSS
        'sanitize_messages' => true,

        // Allowed HTML tags in messages (if sanitization is enabled)
        'allowed_html_tags' => '<b><i><u><a><br><p>',

        // Maximum number of active conversations per user
        'max_conversations_per_user' => env('CHAT_MAX_CONVERSATIONS', 100),
    ],

];
