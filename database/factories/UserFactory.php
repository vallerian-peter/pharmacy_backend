<?php

namespace Database\Factories;

use App\Enum\StatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid'          => Str::uuid()->toString(),
            'name'          => fake()->name(),
            'username'      => fake()->unique()->userName(),
            'email'         => fake()->unique()->safeEmail(),
            'phone'         => fake()->phoneNumber(),
            'password'      => static::$password ??= Hash::make('password'),
            'status'        => StatusEnum::ACTIVE->value,
            'rememberToken' => Str::random(10),   // camelCase — matches column name
        ];
    }

    /**
     * Mark the user as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusEnum::INACTIVE->value,
        ]);
    }
}

