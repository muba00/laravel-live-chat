<?php

namespace muba00\LaravelLiveChat\Commands;

use Illuminate\Console\Command;

class LaravelLiveChatCommand extends Command
{
    public $signature = 'laravel-live-chat';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
