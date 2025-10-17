<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use muba00\LaravelLiveChat\Commands\LaravelLiveChatCommand;

beforeEach(function () {
    // Clean up any published files from previous tests
    $configPath = config_path('live-chat.php');
    if (File::exists($configPath)) {
        File::delete($configPath);
    }
});

it('can run the install command', function () {
    $this->artisan(LaravelLiveChatCommand::class)
        ->assertSuccessful();
});

it('checks PHP version prerequisite', function () {
    $this->artisan(LaravelLiveChatCommand::class)
        ->expectsOutput('🔍 Checking prerequisites...')
        ->assertSuccessful();
});

it('shows Laravel version in prerequisites', function () {
    $laravelVersion = app()->version();

    $this->artisan(LaravelLiveChatCommand::class)
        ->expectsOutputToContain("✓ Laravel version: {$laravelVersion}")
        ->assertSuccessful();
});

it('publishes config file when not skipped', function () {
    $configPath = config_path('live-chat.php');

    expect(File::exists($configPath))->toBeFalse();

    $this->artisan(LaravelLiveChatCommand::class, ['--skip-migrations' => true, '--skip-assets' => true])
        ->assertSuccessful();

    // Config should be published
    expect(File::exists($configPath))->toBeTrue();

    // Clean up
    File::delete($configPath);
});

it('skips config when --skip-config option is provided', function () {
    $configPath = config_path('live-chat.php');

    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-config' => true,
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])->assertSuccessful();

    // Config should not be published
    expect(File::exists($configPath))->toBeFalse();
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
        '--skip-assets' => true,
    ])
        ->expectsOutput('✅ Installation completed successfully!')
        ->expectsOutput('📚 Next Steps:')
        ->assertSuccessful();
});

it('displays broadcasting setup instructions', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('composer require laravel/reverb')
        ->assertSuccessful();
});

it('displays authentication setup instructions', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('composer require laravel/sanctum')
        ->assertSuccessful();
});

it('displays frontend dependencies instructions', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('npm install --save-dev laravel-echo pusher-js')
        ->assertSuccessful();
});

it('can force overwrite existing files', function () {
    $configPath = config_path('live-chat.php');

    // First run
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])->assertSuccessful();

    expect(File::exists($configPath))->toBeTrue();

    // Second run with force
    $this->artisan(LaravelLiveChatCommand::class, [
        '--force' => true,
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])->assertSuccessful();

    expect(File::exists($configPath))->toBeTrue();

    // Clean up
    File::delete($configPath);
});

it('warns when broadcasting is not configured', function () {
    // Temporarily set broadcasting to null
    Config::set('broadcasting.default', null);

    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('⚠ Broadcasting is not configured')
        ->assertSuccessful();
});

it('shows success message when broadcasting is configured', function () {
    Config::set('broadcasting.default', 'reverb');

    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('✓ Broadcasting driver: reverb')
        ->assertSuccessful();
});

it('displays documentation path', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutputToContain('vendor/muba00/laravel-live-chat/docs/')
        ->assertSuccessful();
});

it('shows installation banner', function () {
    $this->artisan(LaravelLiveChatCommand::class, [
        '--skip-migrations' => true,
        '--skip-assets' => true,
    ])
        ->expectsOutput('┌────────────────────────────────────────┐')
        ->expectsOutput('│  Laravel Live Chat - Installation     │')
        ->expectsOutput('└────────────────────────────────────────┘')
        ->assertSuccessful();
});
