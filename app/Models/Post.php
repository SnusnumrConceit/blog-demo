<?php

namespace App\Models;

use App\Models\Contracts\VisibilityModel;
use App\Models\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\PostObserver;
use App\Services\CensorService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $privacy
 * @property ?int $author_id
 * @property ?Carbon $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string $censored_content
 *
 * @property ?User $author
 * @property Collection<PostView> $views
 * @property Collection<Category> $categories
 */
#[ObservedBy(PostObserver::class)]
class Post extends Model implements VisibilityModel
{
    use HasFactory;
    use HasVisibility;

    protected $fillable = [
        'title',
        'content',
        'privacy',
        'published_at',
        'author_id',
    ];

    protected array $dates = [
        'published_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'published_at' => 'datetime:Y-m-d H:i:s'
    ];

    /**
     * Автор поста
     *
     * @return HasOne
     */
    public function author(): HasOne
    {
        return $this->hasOne(related: User::class, foreignKey: 'id', localKey: 'author_id');
    }

    /**
     * Категории
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            related: Category::class,
            table: 'categories_posts',
            foreignPivotKey: 'post_id',
            relatedPivotKey: 'category_id'
        );
    }

    /**
     * Просмотры постов
     *
     * @return HasMany
     */
    public function views(): HasMany
    {
        return $this->hasMany(related: PostView::class, foreignKey: 'post_id', localKey: 'id');
    }

    /**
     * Контент, прошедший цензуру
     *
     * @return string
     */
    public function getCensoredContentAttribute(): string
    {
        return CensorService::censor($this->content);
    }
}
