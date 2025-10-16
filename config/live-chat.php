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
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes that will be registered by the package.
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'chat/api',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the broadcasting behavior for real-time features.
    |
    */
    'broadcasting' => [
        'enabled' => true,
        'channel_prefix' => 'chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Configure pagination for messages and conversations.
    |
    */
    'pagination' => [
        'messages_per_page' => 50,
        'conversations_per_page' => 20,
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
    ],

];
