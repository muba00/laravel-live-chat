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
