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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\PendingMail;
use Illuminate\Mail\SentMessage;
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

                $this->sendMailToRecipients(recipients: $users, mail: new PostCreatedMail($post));

                break;

            case PostUpdated::class:
                $this->sendMailToRecipients(recipients: $users, mail: new PostUpdatedMail($post));

                break;

            case PostDeleted::class:
                $this->sendMailToRecipients(recipients: $users, mail: new PostDeletedMail($post));

                break;
        }
    }

    /**
     * Подготовить письмо
     *
     * @param Collection<User> $recipients
     *
     * @return PendingMail
     */
    protected function getPendingMail(Collection $recipients): PendingMail
    {
        return Mail::to(
            users: $recipients->pluck('email')->all()
        );
    }

    /**
     * Отправка письма
     *
     * @param Collection<User> $recipients
     * @param Mailable $mail
     *
     * @return SentMessage|null
     */
    protected function sendEmail(Collection $recipients, Mailable $mail): ?SentMessage
    {
        return $this->getPendingMail($recipients)->send($mail);
    }

    /**
     * Выборка получателей с отправкой писем
     *
     * @param Collection $recipients
     * @param Mailable $mail
     *
     * @return void
     */
    protected function sendMailToRecipients(Collection $recipients, Mailable $mail): void
    {
        $recipients->when(
            value: $recipients->count(),
            callback: fn () => $recipients->chunk(20)
                ->each(
                    fn (Collection $users) => $this->getPendingMail($recipients)
                        ->send($mail))
                );
    }
}
