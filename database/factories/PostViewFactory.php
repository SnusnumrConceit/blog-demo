<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostView>
 */
class PostViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => fn () => Post::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
        ];
    }

    /**
     * Пользователь
     *
     * @param int $userId
     *
     * @return $this
     */
    public function user(int $userId): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $userId]);
    }

    /**
     * Пост
     *
     * @param int $postId
     *
     * @return $this
     */
    public function post(int $postId): static
    {
        return $this->state(fn (array $attributes) => ['post_id' => $postId]);
    }
}
