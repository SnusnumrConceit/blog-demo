<?php

namespace App\Models;

use App\Models\Contracts\VisibilityModel;
use App\Models\Traits\HasVisibility;
use App\Observers\CategoryObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read int $id
 * @property string $slug
 * @property string $name
 * @property ?string $privacy
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string $display_name
 *
 * @property Collection<Post> $posts
 * @property Collection<Post> $publicPosts
 */
#[ObservedBy(CategoryObserver::class)]
class Category extends Model implements VisibilityModel
{
    use HasFactory;
    use HasVisibility;

    protected $fillable = [
        'slug',
        'name',
        'privacy',
    ];

    protected array $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Посты
     *
     * @return BelongsToMany
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Post::class,
            table: 'categories_posts',
            foreignPivotKey: 'category_id',
            relatedPivotKey: 'post_id'
        );
    }

    /**
     * Опубликованные посты
     *
     * @return BelongsToMany
     */
    public function publicPosts(): BelongsToMany
    {
        return $this->posts()->whereNull('privacy');
    }

    /**
     * Название категории для отображения
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->name);
    }
}
