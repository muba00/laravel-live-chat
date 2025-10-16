<?php

namespace muba00\LaravelLiveChat;

use muba00\LaravelLiveChat\Commands\LaravelLiveChatCommand;
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
}
