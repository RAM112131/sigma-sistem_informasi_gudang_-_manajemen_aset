# Events, Listeners & Broadcasting Reference — Laravel 13

## Table of Contents
1. [Events](#events)
2. [Listeners](#listeners)
3. [Event Discovery](#event-discovery)
4. [Broadcasting (Reverb)](#broadcasting-reverb)
5. [Channels](#channels)

---

## Events

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PostPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Post $post,
        public readonly User $publishedBy,
    ) {}

    // Broadcast on private channel per user
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->post->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.published';
    }

    public function broadcastWith(): array
    {
        return [
            'post_id'    => $this->post->id,
            'title'      => $this->post->title,
            'status'     => $this->post->status->value,
        ];
    }
}
```

Fire: `event(new PostPublished($post, $user));`

---

## Listeners

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostPublished;
use App\Notifications\PostPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class SendPostPublishedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';
    public int $delay = 5; // seconds after event

    public function handle(PostPublished $event): void
    {
        $event->post->author->notify(
            new PostPublishedNotification($event->post)
        );

        Log::info('Post published notification sent', [
            'post_id' => $event->post->id,
        ]);
    }

    public function failed(PostPublished $event, \Throwable $e): void
    {
        Log::error('Failed to send post published notification', [
            'post_id' => $event->post->id,
            'error'   => $e->getMessage(),
        ]);
    }

    // Skip listener under certain conditions
    public function shouldHandle(PostPublished $event): bool
    {
        return $event->post->author->wantsEmailNotifications();
    }
}
```

---

## Event Discovery

Register in `AppServiceProvider::boot()`:

```php
Event::listen(PostPublished::class, SendPostPublishedNotification::class);
Event::listen(PostPublished::class, IndexPostInSearch::class);
Event::listen(PostPublished::class, [UpdateSitemap::class, 'handle']);
```

Or use auto-discovery (scans `app/Listeners`):

```php
// EventServiceProvider — or bootstrap/app.php
Event::discover(app_path('Listeners'));
```

Model event dispatching via `$dispatchesEvents`:

```php
// In Post model
protected $dispatchesEvents = [
    'created' => PostCreated::class,
    'updated' => PostUpdated::class,
    'deleted' => PostDeleted::class,
];
```

---

## Broadcasting (Reverb)

Laravel 13 ships a **database driver** for Reverb horizontal scaling — no Redis required for most apps.

```bash
# Install Reverb
composer require laravel/reverb
php artisan reverb:install

# Start
php artisan reverb:start
php artisan reverb:start --debug  # development
```

```php
// config/broadcasting.php
'reverb' => [
    'driver'  => 'reverb',
    'key'     => env('REVERB_APP_KEY'),
    'secret'  => env('REVERB_APP_SECRET'),
    'app_id'  => env('REVERB_APP_ID'),
    'options' => [
        'host'   => env('REVERB_HOST', '0.0.0.0'),
        'port'   => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
    ],
    'scaling' => [
        'enabled'    => env('REVERB_SCALING_ENABLED', false),
        'channel'    => env('REVERB_SCALING_CHANNEL', 'reverb'),
        // L13: use 'database' driver instead of Redis for small/medium apps
        'connection' => env('REVERB_SCALING_CONNECTION', 'database'),
    ],
],
```

Frontend (Echo):

```js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
})

// Listen
Echo.private(`user.${userId}`)
    .listen('.post.published', (event) => {
        console.log('Post published:', event.post_id)
    })
```

---

## Channels

```php
// routes/channels.php
use App\Models\Post;
use App\Models\User;

Broadcast::channel('user.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId;
});

Broadcast::channel('post.{post}', function (User $user, Post $post): bool {
    return $user->can('view', $post);
});

// Presence channel — returns user data for participant list
Broadcast::channel('room.{roomId}', function (User $user, int $roomId): ?array {
    if ($user->rooms->contains($roomId)) {
        return ['id' => $user->id, 'name' => $user->name];
    }
    return null;
});
```
