<?php

namespace App\Models;

use App\Observers\CategoryObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $slug
 * @property string $name
 * @property ?string $privacy
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Category extends Model
{
    use HasFactory;

    protected static function boot()
    {
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
}
