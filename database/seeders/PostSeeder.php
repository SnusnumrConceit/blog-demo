<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::factory()->count(1000)->create();
        $categories = Category::all();

        $posts->chunk(100)
            ->each(function(Collection $posts) use ($categories) {
                $postCategories = [];

                $posts->each(function (Post $post) use (&$postCategories, $categories) {
                    $categories->random(5)
                        ->pluck('id')
                        ->each(function (int $categoryId) use (&$postCategories, $post) {
                            $postCategories[] = ['post_id' => $post->id, 'category_id' => $categoryId];
                        });
                });

                DB::table('categories_posts')->insert($postCategories);
            });
    }
}
