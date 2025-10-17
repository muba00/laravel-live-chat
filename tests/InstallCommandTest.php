<?php

use Illuminate\Support\Facades\Config;
use muba00\LaravelLiveChat\Commands\LaravelLiveChatCommand;

beforeEach(function () {
    // Ensure we're in non-interactive mode for testing
    $this->app['env'] = 'testing';
});

it('can run the install command', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])->assertSuccessful();
});

it('checks PHP version prerequisite', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutput('🔍 Checking prerequisites...')
        ->assertSuccessful();
});

it('shows Laravel version in prerequisites', function () {
    $laravelVersion = app()->version();

    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain("✓ Laravel version: {$laravelVersion}")
        ->assertSuccessful();
});

it('publishes config file when not skipped', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutput('📝 Publishing configuration...')
        ->assertSuccessful();
});

it('skips config when --skip-config option is provided', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-config' => true,
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->doesntExpectOutput('📝 Publishing configuration...')
        ->assertSuccessful();
});

it('skips migrations when --skip-migrations option is provided', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-config' => true,
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->doesntExpectOutput('📦 Publishing migrations...')
        ->assertSuccessful();
});

it('skips assets when --skip-assets option is provided', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-config' => true,
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->doesntExpectOutput('🎨 Publishing frontend assets...')
        ->assertSuccessful();
});

it('shows next steps after installation', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutput('✅ Installation completed successfully!')
        ->expectsOutput('📚 Next Steps:')
        ->assertSuccessful();
});

it('displays broadcasting setup instructions', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('composer require laravel/reverb')
        ->assertSuccessful();
});

it('displays authentication setup instructions', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('composer require laravel/sanctum')
        ->assertSuccessful();
});

it('displays frontend dependencies instructions', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('npm install --save-dev laravel-echo pusher-js')
        ->assertSuccessful();
});

it('can force overwrite existing files', function () {
    // First run
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])->assertSuccessful();

    // Second run with force
    $this->artisan(LaravelLiveChatCommand::class, [
        '--force' => true,
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutput('📝 Publishing configuration...')
        ->assertSuccessful();
});

it('warns when broadcasting is not configured', function () {
    // Temporarily set broadcasting to null
    Config::set('broadcasting.default', null);

    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('⚠ Broadcasting is not configured')
        ->assertSuccessful();
});

it('shows success message when broadcasting is configured', function () {
    Config::set('broadcasting.default', 'reverb');

    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('✓ Broadcasting driver: reverb')
        ->assertSuccessful();
});

it('displays documentation path', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('vendor/muba00/laravel-live-chat/docs/')
        ->assertSuccessful();
});

it('shows installation banner', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutput('┌────────────────────────────────────────┐')
        ->expectsOutput('│  Laravel Live Chat - Installation     │')
        ->expectsOutput('└────────────────────────────────────────┘')
        ->assertSuccessful();
});

it('publishes migrations when not skipped', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-config' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutput('📦 Publishing migrations...')
        ->assertSuccessful();
});

it('publishes assets when not skipped', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-config' => true,
        '--skip-migrations' => true,
    ])
        ->expectsOutput('🎨 Publishing frontend assets...')
        ->assertSuccessful();
});
