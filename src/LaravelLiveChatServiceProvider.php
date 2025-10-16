<?php

namespace muba00\LaravelLiveChat;

use Illuminate\Support\Facades\Broadcast;
use muba00\LaravelLiveChat\Commands\LaravelLiveChatCommand;
use muba00\LaravelLiveChat\Models\Conversation;
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
        $this->publishFrontendAssets();
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
