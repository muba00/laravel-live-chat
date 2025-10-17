<?php

namespace muba00\LaravelLiveChat\Commands;

use Illuminate\Console\Command;

class LaravelLiveChatCommand extends Command
{
    public $signature = 'live-chat:install
                        {--force : Overwrite existing files}
                        {--skip-migrations : Skip publishing migrations}
                        {--skip-config : Skip publishing config}
                        {--skip-assets : Skip publishing frontend assets}';

    public $description = 'Install Laravel Live Chat package';

    public function handle(): int
    {
        $this->info('┌────────────────────────────────────────┐');
        $this->info('│  Laravel Live Chat - Installation     │');
        $this->info('└────────────────────────────────────────┘');
        $this->newLine();

        // Check prerequisites
        if (! $this->checkPrerequisites()) {
            return self::FAILURE;
        }

        // Publish config
        if (! $this->option('skip-config')) {
            $this->publishConfig();
        }

        // Publish migrations
        if (! $this->option('skip-migrations')) {
            $this->publishMigrations();
        }

        // Publish frontend assets
        if (! $this->option('skip-assets')) {
            $this->publishAssets();
        }

        // Show next steps
        $this->showNextSteps();

        return self::SUCCESS;
    }

    protected function checkPrerequisites(): bool
    {
        $this->info('🔍 Checking prerequisites...');

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            $this->error('✗ PHP 8.3 or higher is required. Current version: '.PHP_VERSION);

            return false;
        }
        $this->line('  ✓ PHP version: '.PHP_VERSION);

        // Check Laravel version
        $laravelVersion = app()->version();
        if (version_compare($laravelVersion, '11.0', '<')) {
            $this->error('✗ Laravel 11 or higher is required. Current version: '.$laravelVersion);

            return false;
        }
        $this->line('  ✓ Laravel version: '.$laravelVersion);

        // Check for broadcasting configuration
        if (! config('broadcasting.default')) {
            $this->warn('  ⚠ Broadcasting is not configured. You will need to set this up for real-time features.');
        } else {
            $this->line('  ✓ Broadcasting driver: '.config('broadcasting.default'));
        }

        // Check for Sanctum
        if (! class_exists('Laravel\Sanctum\Sanctum')) {
            $this->warn('  ⚠ Laravel Sanctum is not installed. API authentication will require setup.');
        } else {
            $this->line('  ✓ Laravel Sanctum is installed');
        }

        $this->newLine();

        return true;
    }

    protected function publishConfig(): void
    {
        $this->info('📝 Publishing configuration...');

        $params = [
            '--provider' => "muba00\LaravelLiveChat\LaravelLiveChatServiceProvider",
            '--tag' => 'laravel-live-chat-config',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
        $this->line('  ✓ Config file published to: config/live-chat.php');
        $this->newLine();
    }

    protected function publishMigrations(): void
    {
        $this->info('📦 Publishing migrations...');

        $params = [
            '--provider' => "muba00\LaravelLiveChat\LaravelLiveChatServiceProvider",
            '--tag' => 'laravel-live-chat-migrations',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
        $this->line('  ✓ Migrations published to: database/migrations/');

        // Skip confirmation in non-interactive mode (e.g., testing)
        if (! $this->input->isInteractive() || $this->confirm('Would you like to run migrations now?', true)) {
            $this->newLine();
            $this->info('🔄 Running migrations...');
            $this->call('migrate');
            $this->line('  ✓ Migrations completed successfully');
        } else {
            $this->line('  ℹ Remember to run "php artisan migrate" later');
        }

        $this->newLine();
    }

    protected function publishAssets(): void
    {
        $this->info('🎨 Publishing frontend assets...');

        $params = [
            '--provider' => "muba00\LaravelLiveChat\LaravelLiveChatServiceProvider",
            '--tag' => 'live-chat-frontend',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
        $this->line('  ✓ Blade components published to: resources/views/vendor/live-chat/');
        $this->line('  ✓ JavaScript files published to: resources/js/vendor/live-chat/');
        $this->line('  ✓ CSS files published to: resources/css/vendor/live-chat/');
        $this->newLine();
    }

    protected function showNextSteps(): void
    {
        $this->info('✅ Installation completed successfully!');
        $this->newLine();
        $this->info('📚 Next Steps:');
        $this->newLine();

        $this->line('1. Configure broadcasting (Laravel Reverb recommended):');
        $this->line('   composer require laravel/reverb');
        $this->line('   php artisan reverb:install');
        $this->newLine();

        $this->line('2. Configure authentication (if not already done):');
        $this->line('   composer require laravel/sanctum');
        $this->line('   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"');
        $this->newLine();

        $this->line('3. Review configuration file:');
        $this->line('   config/live-chat.php');
        $this->newLine();

        $this->line('4. Install frontend dependencies:');
        $this->line('   npm install --save-dev laravel-echo pusher-js');
        $this->newLine();

        $this->line('5. Check out the documentation:');
        $this->line('   vendor/muba00/laravel-live-chat/docs/');
        $this->newLine();

        $this->info('🚀 You\'re all set! Start building your chat application.');
        $this->newLine();
    }
}
