<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CensorService
{
    /**
     * Получение нецензурных слов
     *
     * @return array
     */
    public static function getStopWords(): array
    {
        return Cache::remember(
            key: 'posts.uncensored',
            ttl: 1800,
            callback: fn () => explode(
                separator: "\n",
                string: file_get_contents(resource_path('files/stop-words.txt'))
            ),
        );
    }

    /**
     * Цензура контента
     *
     * @param string $text
     *
     * @return string
     */
    public static function censor(string $text): string
    {
        foreach (static::getStopWords() as $word) {
            if (str_contains(haystack: $text, needle: $word)) {
                $text = str_replace(
                    search: $word,
                    replace: Str::mask(string: $word, character: '*', index: 0),
                    subject: $text
                );
            }
        }

        return $text;
    }
}
