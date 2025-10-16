<?php

namespace muba00\LaravelLiveChat;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use muba00\LaravelLiveChat\Commands\LaravelLiveChatCommand;

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
            ->hasMigration('create_laravel_live_chat_table')
            ->hasCommand(LaravelLiveChatCommand::class);
    }
}
