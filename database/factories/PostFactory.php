<?php

namespace Database\Factories;

use App\Enums\Post\PrivacyEnum;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->text(100);

        return [
            'title' => $title,
            'slug' => Str::slug(title: $title, language: 'ru'),
            'privacy' => Arr::random([null, PrivacyEnum::getRandomValue()]),
            'content' => $this->faker->text(),
            'author_id' => fn () => User::factory()->create()->id,
            'published_at' => null,
        ];
    }

    /**
     * Простой (системный?) пост
     * - нет автора
     * - нет запланированной даты публикации
     *
     * @return $this
     */
    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => null,
            'published_at' => null,
        ]);
    }

    /**
     * Авторский пост
     *
     * @param int $authorId
     *
     * @return $this
     */
    public function authoredBy(int $authorId): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => $authorId
        ]);
    }

    /**
     * Запланированная публикация
     *
     * @param Carbon|string $publishedAt
     *
     * @return $this
     */
    public function planned(Carbon|string $publishedAt): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $publishedAt instanceof Carbon ? $publishedAt->toDateTimeString() : $publishedAt
        ]);
    }

    /**
     * Публичный
     *
     * @return PostFactory
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => null
        ]);
    }

    /**
     * Скрыт от гостей
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
     * Скрыт от всех
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
