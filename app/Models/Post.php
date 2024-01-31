<?php

namespace App\Models;

use App\Enums\Post\PrivacyEnum;
use App\Observers\PostObserver;
use App\Services\CensorService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
 * @property \Illuminate\Database\Eloquent\Collection<Category> $categories
 */
class Post extends Model
{
    use HasFactory;

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
        'published_at' => 'datetime:Y.m.d H:i:s'
    ];

    protected static function boot()
    {
        parent::boot();

        static::observe(PostObserver::class);
    }

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
     * Доступен всем
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return is_null($this->privacy);
    }


    /**
     * Доступен активным
     *
     * @return bool
     */
    public function isProtected(): bool
    {
        return $this->privacy === PrivacyEnum::PROTECTED;
    }

    /**
     * Скрыт от всех
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->privacy === PrivacyEnum::PRIVATE;
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
