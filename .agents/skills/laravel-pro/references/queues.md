# Queues, Jobs & Horizon Reference — Laravel 13

## Table of Contents

1. [Job Classes](#job-classes)
2. [Queue Routing (L13)](#queue-routing-l13)
3. [Dispatching Jobs](#dispatching-jobs)
4. [Job Batching](#job-batching)
5. [Chained Jobs](#chained-jobs)
6. [Rate Limiting](#rate-limiting)
7. [Horizon Configuration](#horizon-configuration)
8. [Failed Jobs](#failed-jobs)

---

## Job Classes

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutOverlapping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[WithoutOverlapping(key: 'post-{post.id}', releaseAfter: 60)]
final class ProcessPostPublish implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Retry configuration
    public int $tries = 3;
    public int $maxExceptions = 2;

    // Timeout per attempt (seconds)
    public int $timeout = 120;

    // Exponential backoff: 30s, 60s, 120s
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    // Unique key — only one job per post at a time
    public function uniqueId(): string
    {
        return (string) $this->post->id;
    }

    public function __construct(
        private readonly Post $post,
        private readonly User $triggeredBy,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $this->post->update([
            'status'       => PostStatus::Published,
            'published_at' => now(),
        ]);

        Log::info('Post published via queue', [
            'post_id'    => $this->post->id,
            'user_id'    => $this->triggeredBy->id,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessPostPublish failed', [
            'post_id' => $this->post->id,
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        // Notify the author the publish failed
        $this->triggeredBy->notify(new PostPublishFailedNotification($this->post));
    }
}
```

---

## Queue Routing (L13)

Laravel 13 adds `Queue::route()` — define default queue/connection per job class without touching each job.

```php
// AppServiceProvider::boot()
use Illuminate\Support\Facades\Queue;

Queue::route([
    ProcessPostPublish::class      => 'high',
    NotifySubscribers::class       => 'notifications',
    GeneratePostEmbedding::class   => 'ai',
    SendWelcomeEmail::class        => 'mail',
]);

// Override per-dispatch still works:
ProcessPostPublish::dispatch($post)->onQueue('critical');
```

This supersedes setting `$queue` property on each job.

---

## Dispatching Jobs

```php
// Immediate
ProcessPostPublish::dispatch($post, $user);

// Delayed
ProcessPostPublish::dispatch($post, $user)->delay(now()->addMinutes(10));

// Conditional
ProcessPostPublish::dispatchIf($post->isReadyToPublish(), $post, $user);
ProcessPostPublish::dispatchUnless($post->isArchived(), $post, $user);

// On specific connection/queue
ProcessPostPublish::dispatch($post, $user)
    ->onConnection('redis')
    ->onQueue('high');

// After commit — only fires if the current DB transaction commits
ProcessPostPublish::dispatch($post, $user)->afterCommit();

// From service
dispatch(new ProcessPostPublish($post, $user));
dispatch_sync(new ProcessPostPublish($post, $user)); // run synchronously
```

---

## Job Batching

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new GenerateThumbnail($post, 'sm'),
    new GenerateThumbnail($post, 'md'),
    new GenerateThumbnail($post, 'lg'),
])
->then(function (Batch $batch): void {
    Log::info('All thumbnails generated', ['post_id' => $post->id]);
})
->catch(function (Batch $batch, \Throwable $e): void {
    Log::error('Batch failed', ['error' => $e->getMessage()]);
})
->finally(function (Batch $batch): void {
    // Always runs — success or failure
})
->allowFailures()           // don't cancel batch on single job failure
->onQueue('media')
->dispatch();

// Store batch ID to poll progress
$post->update(['batch_id' => $batch->id]);

// Check progress
$batch = Bus::findBatch($batchId);
$batch->progress(); // 0-100
$batch->finished(); // bool
```

---

## Chained Jobs

```php
ProcessPostPublish::withChain([
    new GeneratePostSummary($post),
    new NotifySubscribers($post),
    new PingSearchIndex($post),
])->dispatch($post, $user);
```

---

## Rate Limiting

```php
// In job class
public function middleware(): array
{
    return [
        new RateLimited('ai-processing'),  // defined in AppServiceProvider
        new WithoutOverlapping('post-'.$this->post->id, expireAfter: 300),
        new ThrottlesExceptions(maxAttempts: 10, decaySeconds: 300),
    ];
}

// Define rate limiter
RateLimiter::for('ai-processing', fn () =>
    Limit::perMinute(20)->by('ai')
);
```

---

## Horizon Configuration

```php
// config/horizon.php — key sections

'environments' => [
    'production' => [
        'supervisor-default' => [
            'maxProcesses'  => 10,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
        ],
        'supervisor-ai' => [
            'queue'         => ['ai'],
            'maxProcesses'  => 3,
            'timeout'       => 600,
        ],
        'supervisor-mail' => [
            'queue'         => ['mail', 'notifications'],
            'maxProcesses'  => 5,
        ],
    ],

    'local' => [
        'supervisor-default' => [
            'maxProcesses' => 3,
        ],
    ],
],

'balance'            => 'auto',   // 'simple', 'auto', 'false'
'memory_limit'       => 512,      // MB — restart if exceeded
'timeout'            => 60,
'trim'               => [
    'recent'         => 60,
    'pending'        => 60,
    'completed'      => 60,
    'recent_failed'  => 10080,
    'failed'         => 10080,
    'monitored'      => 10080,
],
```

```bash
php artisan horizon          # start
php artisan horizon:pause
php artisan horizon:continue
php artisan horizon:terminate  # graceful shutdown for deployment
```

---

## Failed Jobs

```php
// Retry a specific job
php artisan queue:retry <uuid>
php artisan queue:retry all

// Flush all failed
php artisan queue:flush

// Forget one
php artisan queue:forget <uuid>

// In job — delay retry based on exception type
public function failed(\Throwable $e): void
{
    if ($e instanceof RateLimitException) {
        $this->release(60); // retry in 60 seconds
        return;
    }

    // Permanent failure — notify and log
    Log::critical('Job failed permanently', ['error' => $e->getMessage()]);
    Notification::route('slack', config('services.slack.webhook'))
        ->notify(new JobFailedNotification($this, $e));
}
```
