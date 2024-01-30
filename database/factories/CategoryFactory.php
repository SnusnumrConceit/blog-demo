<?php

namespace Database\Factories;

use App\Enums\Category\PrivacyEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->text(100);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'privacy' => Arr::random([null, PrivacyEnum::getRandomValue()]),
        ];
    }

    /**
     * Публичная
     *
     * @return $this
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => null,
        ]);
    }

    /**
     * Скрыта от гостей
     *
     * @return $this
     */
    public function protected(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => PrivacyEnum::PROTECTED
        ]);
    }

    /**
     * Скрыта от всех
     *
     * @return $this
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
           'privacy' => PrivacyEnum::PRIVATE
        ]);
    }
}
