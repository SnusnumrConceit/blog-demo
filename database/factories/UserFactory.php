<?php

namespace Database\Factories;

use App\Enums\User\StatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Пользователь по умолчанию
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => StatusEnum::getRandomValue(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Админ
     *
     * @return $this
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
           'status' => StatusEnum::ADMIN,
        ]);
    }

    /**
     * Активный
     *
     * @return $this
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusEnum::ACTIVE,
        ]);
    }

    /**
     * Заблокированный
     *
     * @return $this
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusEnum::BLOCKED,
        ]);
    }

    /**
     * Неподтверждённый пользователь (по email)
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'status' => StatusEnum::EMAIL_VERIFICATION,
        ]);
    }
}
