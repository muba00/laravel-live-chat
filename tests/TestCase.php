<?php

namespace muba00\LaravelLiveChat\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use muba00\LaravelLiveChat\LaravelLiveChatServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure factory namespace resolution for package factories
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'muba00\\LaravelLiveChat\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Create users table for testing (not part of package - for test User model only)
        $this->createUsersTable();

        // Run package migrations
        $this->runPackageMigrations();
    }

    /**
     * Create the users table for testing purposes.
     */
    protected function createUsersTable(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
    }

    /**
     * Run package migration stubs.
     */
    protected function runPackageMigrations(): void
    {
        $migrationPath = __DIR__.'/../database/migrations';

        $migrations = [
            'create_live_chat_conversations_table.php.stub',
            'create_live_chat_messages_table.php.stub',
        ];

        foreach ($migrations as $migration) {
            // Check if we should skip based on table existence
            // RefreshDatabase will handle dropping/recreating as needed
            $migrationClass = include $migrationPath.'/'.$migration;

            // Only run if tables don't exist (RefreshDatabase will manage them)
            if ($migration === 'create_live_chat_conversations_table.php.stub' && Schema::hasTable('live_chat_conversations')) {
                continue;
            }
            if ($migration === 'create_live_chat_messages_table.php.stub' && Schema::hasTable('live_chat_messages')) {
                continue;
            }

            $migrationClass->up();
        }
    }

    /**
     * Get package service providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelLiveChatServiceProvider::class,
        ];
    }

    /**
     * Configure the test environment.
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Database configuration
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Authentication configuration (using web guard instead of Sanctum for simplicity)
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        config()->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \muba00\LaravelLiveChat\Tests\Stubs\User::class,
        ]);

        // Package configuration
        config()->set('live-chat.user_model', \muba00\LaravelLiveChat\Tests\Stubs\User::class);
        config()->set('live-chat.routes.middleware', ['api', 'auth:web']);
    }
}
