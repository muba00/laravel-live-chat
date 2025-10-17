<?php

namespace muba00\LaravelLiveChat\Tests\Stubs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Test User Model
 *
 * A simplified User model for package testing purposes.
 * Extends Laravel's Authenticatable class to provide clean authentication support.
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \muba00\LaravelLiveChat\Tests\Factories\UserFactory::new();
    }
}
