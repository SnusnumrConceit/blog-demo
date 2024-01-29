<?php

namespace App\Models;

use App\Enums\Category\PrivacyEnum;
use App\Observers\CategoryObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;

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
 */
class Category extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::observe(CategoryObserver::class);

    }

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

    /**
     * Выборка по публичным категориям
     *
     * @return Builder
     */
    public function scopePublic(): Builder
    {
        return $this->whereNull('privacy');
    }

    /**
     * Выборка по защищённым категориям
     *
     * @return Builder
     */
    public function scopeProtected(): Builder
    {
        return $this->where('privacy', PrivacyEnum::PROTECTED);
    }

    /**
     * Выборка по скрытым категориям
     *
     * @return Builder
     */
    public function scopePrivate(): Builder
    {
        return $this->where('privacy', PrivacyEnum::PRIVATE);
    }
}
