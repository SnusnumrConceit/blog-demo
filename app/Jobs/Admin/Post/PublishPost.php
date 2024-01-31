<?php

namespace App\Jobs\Admin\Post;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPost implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $postId,
        public readonly ?string $privacy)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info(vsprintf(
            '%s: публикация поста %d',
            [
                class_basename(static::class),
                $this->postId,
            ]
        ));

         Post::where('id', $this->postId)->update([
             'privacy' => $this->privacy
         ]);
    }

    public function fail($exception = null)
    {
        $this->release(60);
    }
}
