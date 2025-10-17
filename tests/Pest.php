<?php

use muba00\LaravelLiveChat\Tests\Stubs\User;
use muba00\LaravelLiveChat\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Create a test user with the given name and email.
 * 
 * This is a convenience wrapper around the User factory for tests that
 * need to create users with specific attributes.
 */
function createUser(string $name, string $email): User
{
    return User::factory()->create([
        'name' => $name,
        'email' => $email,
    ]);
}
