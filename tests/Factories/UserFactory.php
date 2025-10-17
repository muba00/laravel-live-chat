<?php

namespace muba00\LaravelLiveChat\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use muba00\LaravelLiveChat\Tests\Stubs\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ];
    }
}
