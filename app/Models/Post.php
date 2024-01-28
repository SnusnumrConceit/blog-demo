<?php

namespace App\Models;

use App\Enums\Post\PrivacyEnum;
use App\Observers\PostObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
