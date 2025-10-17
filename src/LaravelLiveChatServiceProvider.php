<?php

namespace muba00\LaravelLiveChat;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use muba00\LaravelLiveChat\Commands\LaravelLiveChatCommand;
use muba00\LaravelLiveChat\Http\Controllers\ConversationController;
use muba00\LaravelLiveChat\Http\Controllers\MessageController;
use muba00\LaravelLiveChat\Http\Controllers\TypingController;
use muba00\LaravelLiveChat\Models\Conversation;
use muba00\LaravelLiveChat\Policies\ConversationPolicy;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelLiveChatServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-live-chat')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_live_chat_conversations_table',
                'create_live_chat_messages_table',
            ])
            ->hasCommand(LaravelLiveChatCommand::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function packageBooted(): void
    {
        $this->registerBroadcastChannels();
        $this->registerRoutes();
        $this->registerPolicies();
        $this->publishFrontendAssets();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (! config('live-chat.routes.enabled', true)) {
            return;
        }

        $routeConfig = config('live-chat.routes', []);
        $prefix = $routeConfig['prefix'] ?? 'chat/api';
        $middleware = $routeConfig['middleware'] ?? ['api', 'auth:sanctum'];

        Route::prefix($prefix)
            ->middleware($middleware)
            ->group(function () {
                // Conversation routes
                Route::get('/conversations', [ConversationController::class, 'index'])
                    ->name('chat.conversations.index');
                Route::post('/conversations', [ConversationController::class, 'store'])
                    ->name('chat.conversations.store');
                Route::get('/conversations/{conversationId}', [ConversationController::class, 'show'])
                    ->name('chat.conversations.show');
                Route::delete('/conversations/{conversationId}', [ConversationController::class, 'destroy'])
                    ->name('chat.conversations.destroy');

                // Message routes
                Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'index'])
                    ->name('chat.messages.index');
                Route::post('/conversations/{conversationId}/messages', [MessageController::class, 'store'])
                    ->name('chat.messages.store');
                Route::post('/conversations/{conversationId}/messages/mark-read', [MessageController::class, 'markAsRead'])
                    ->name('chat.messages.markAsRead');
                Route::get('/conversations/{conversationId}/messages/unread-count', [MessageController::class, 'unreadCount'])
                    ->name('chat.messages.unreadCount');

                // Typing indicator routes
                Route::post('/conversations/{conversationId}/typing', [TypingController::class, 'store'])
                    ->name('chat.typing.store');
            });
    }

    /**
     * Register the package policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Conversation::class, ConversationPolicy::class);
    }

    /**
     * Publish frontend assets (views, JavaScript, CSS).
     */
    protected function publishFrontendAssets(): void
    {
        // Publish views (Blade components)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/live-chat'),
        ], 'live-chat-views');

        // Publish JavaScript helpers
        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/live-chat'),
        ], 'live-chat-js');

        // Publish CSS styles
        $this->publishes([
            __DIR__.'/../resources/css' => resource_path('css/vendor/live-chat'),
        ], 'live-chat-css');

        // Publish all frontend assets at once
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/live-chat'),
            __DIR__.'/../resources/js' => resource_path('js/vendor/live-chat'),
            __DIR__.'/../resources/css' => resource_path('css/vendor/live-chat'),
        ], 'live-chat-frontend');
    }

    /**
     * Register the broadcasting channels for live chat.
     */
    protected function registerBroadcastChannels(): void
    {
        if (! config('live-chat.broadcasting.enabled', true)) {
            return;
        }

        $channelPrefix = config('live-chat.broadcasting.channel_prefix', 'chat');

        Broadcast::channel("{$channelPrefix}.{conversationId}", function ($user, int $conversationId) {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return false;
            }

            // User can access the channel if they are part of the conversation
            return $conversation->includesUser($user);
        });
    }
}
