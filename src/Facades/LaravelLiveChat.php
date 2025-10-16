<?php

namespace muba00\LaravelLiveChat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \muba00\LaravelLiveChat\LaravelLiveChat
 */
class LaravelLiveChat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \muba00\LaravelLiveChat\LaravelLiveChat::class;
    }
}
