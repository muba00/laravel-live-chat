<?php

use Illuminate\Support\Facades\DB;
use muba00\LaravelLiveChat\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

// Helper function to create a User object for testing
function createUser(string $name, string $email): object
{
    $userId = DB::table('users')->insertGetId([
        'name' => $name,
        'email' => $email,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Return as an object with id property
    return (object) ['id' => $userId, 'name' => $name, 'email' => $email];
}

