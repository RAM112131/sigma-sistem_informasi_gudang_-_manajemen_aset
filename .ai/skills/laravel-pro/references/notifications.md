# Notifications, Mail & Slack Reference — Laravel 13

## Table of Contents
1. [Notifications](#notifications)
2. [Mail](#mail)
3. [Slack](#slack)
4. [Database Notifications](#database-notifications)
5. [Notification Routing](#notification-routing)

---

## Notifications

```bash
php artisan make:notification PostPublishedNotification
```

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

final class PostPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $queue = 'notifications';

    public function __construct(private readonly Post $post) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->slack_webhook_url) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your post \"{$this->post->title}\" is live")
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your post has been published successfully.')
            ->action('View Post', route('posts.show', $this->post))
            ->line('Thanks for writing with us!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id'    => $this->post->id,
            'post_title' => $this->post->title,
            'post_url'   => route('posts.show', $this->post),
        ];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->success()
            ->content("Post published: *{$this->post->title}*")
            ->attachment(function ($attachment): void {
                $attachment
                    ->title($this->post->title, route('posts.show', $this->post))
                    ->fields([
                        'Status'  => $this->post->status->value,
                        'Author'  => $this->post->author->name,
                    ]);
            });
    }

    // Send later — throttle/deduplicate
    public function shouldSend(object $notifiable, string $channel): bool
    {
        return ! $notifiable->notifications()
            ->where('type', static::class)
            ->where('created_at', '>=', now()->subHour())
            ->exists();
    }
}
```

Send:

```php
// To a single notifiable
$user->notify(new PostPublishedNotification($post));

// On-demand (no model needed)
Notification::route('mail', 'admin@example.com')
    ->route('slack', config('services.slack.webhook'))
    ->notify(new PostPublishedNotification($post));

// To multiple users
Notification::send(User::subscribed()->get(), new PostPublishedNotification($post));
```

---

## Mail

```bash
php artisan make:mail WelcomeMail --markdown=emails.welcome
```

```php
<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('hello@example.com', 'Example App'),
            replyTo: [new Address('support@example.com', 'Support')],
            subject: "Welcome to Example, {$this->user->name}!",
            tags: ['welcome', 'onboarding'],
            metadata: ['user_id' => $this->user->id],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: ['loginUrl' => route('login')],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

Send:

```php
Mail::to($user)->send(new WelcomeMail($user));
Mail::to($user)->queue(new WelcomeMail($user));
Mail::to($user)->later(now()->addMinutes(5), new WelcomeMail($user));
```

---

## Database Notifications

User model needs `Notifiable` trait.

```bash
php artisan notifications:table
php artisan migrate
```

```php
// Read notifications
$user->unreadNotifications;
$user->notifications()->latest()->paginate(20);

// Mark as read
$user->unreadNotifications->markAsRead();
$notification->markAsRead();

// Delete
$user->notifications()->delete();
```

---

## Notification Routing

```php
// On User model
public function routeNotificationForMail(): string
{
    return $this->email;
}

public function routeNotificationForSlack(): string
{
    return $this->slack_webhook_url;
}

public function routeNotificationForVonage(): string  // SMS via Vonage
{
    return $this->phone;
}
```
