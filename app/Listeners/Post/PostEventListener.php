<?php

namespace App\Listeners\Post;

use App\Contracts\Events\Post\PostEvent;
use App\Events\Post\PostCreated;
use App\Events\Post\PostDeleted;
use App\Events\Post\PostUpdated;
use App\Jobs\Admin\Post\PublishPost;
use App\Mail\Post\PostCreated as PostCreatedMail;
use App\Mail\Post\PostDeleted as PostDeletedMail;
use App\Mail\Post\PostUpdated as PostUpdatedMail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class PostEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PostEvent $event): void
    {
        /** @var Post $post */
        $post = $event->post;

        /** @var Collection<User> $users */
        $users = User::select(['email'])->active()
            ->when(
                value: ! auth()->user()->isAdmin(),
                callback: fn (Builder $query) => $query->where('id', '<>', auth()->id())
            )->get();

        switch ($event::class) {
            case PostCreated::class:
                if ($event->post->published_at) {
                    dispatch(new PublishPost(postId: $post->id, privacy: $event->privacy))
                        ->delay(now()->diffInSeconds($post->published_at));
                }

                $users->when(
                    value: $users->count(),
                    callback: fn () => $users->chunk(20)
                        ->each(
                            fn (Collection $users) => Mail::to(
                                users: $users->pluck('email')->all()
                            )->send(new PostCreatedMail($post))
                        )
                );

                break;

            case PostUpdated::class:
                $users->when(
                    value: $users->count(),
                    callback: fn () => $users->chunk(20)
                        ->each(
                            fn (Collection $users) => Mail::to(
                                users: $users->pluck('email')->all()
                            )->send(new PostUpdatedMail($post))
                        )
                );
                break;

            case PostDeleted::class:
                $users->when(
                    value: $users->count(),
                    callback: fn () => $users->chunk(20)
                        ->each(
                            fn (Collection $users) => Mail::to(
                                users: $users->pluck('email')->all()
                            )->send(new PostDeletedMail($post))
                        )
                );
                break;
        }
    }
}
