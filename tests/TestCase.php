<?php

namespace muba00\LaravelLiveChat\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use muba00\LaravelLiveChat\LaravelLiveChatServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'muba00\\LaravelLiveChat\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function tearDown(): void
    {
        $this->flushHandlers();

        parent::tearDown();
    }

    protected function flushHandlers(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelLiveChatServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
