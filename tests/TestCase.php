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

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'muba00\\LaravelLiveChat\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Create users table for testing (exception - not part of package)
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }

        // Run package migration stubs
        $this->runPackageMigrations();
    }

    /**
     * Run migration stubs for testing
     */
    protected function runPackageMigrations(): void
    {
        $migrationPath = __DIR__.'/../database/migrations';
        
        $migrations = [
            'create_live_chat_conversations_table.php.stub',
            'create_live_chat_messages_table.php.stub',
        ];

        foreach ($migrations as $migration) {
            (include $migrationPath.'/'.$migration)->up();
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelLiveChatServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        // Enable foreign key constraints for SQLite
        config()->set('database.connections.testing.foreign_key_constraints', true);
    }
}
