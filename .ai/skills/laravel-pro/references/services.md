# Services, Repositories & Architecture Reference — Laravel 13

## Table of Contents
1. [Service Layer Pattern](#service-layer-pattern)
2. [Dependency Injection & Container](#dependency-injection--container)
3. [Action Classes](#action-classes)
4. [Repository Pattern](#repository-pattern)
5. [Data Transfer Objects](#data-transfer-objects)
6. [Transactions](#transactions)
7. [Service Provider Bindings](#service-provider-bindings)

---

## Service Layer Pattern

Controllers handle HTTP. Services handle business logic. Models handle data.

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PostPublished;
use App\Jobs\NotifySubscribers;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PostService
{
    public function __construct(
        private readonly TagService $tagService,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['author:id,name,email', 'tags:id,name'])
            ->published()
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function create(array $data): Post
    {
        return DB::transaction(function () use ($data): Post {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $post = Post::create($data);
            $post->tags()->sync($tags);

            return $post->load(['author', 'tags']);
        });
    }

    public function update(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data): Post {
            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            $post->update($data);

            if ($tags !== null) {
                $post->tags()->sync($tags);
            }

            return $post->fresh(['author', 'tags']);
        });
    }

    public function publish(Post $post): Post
    {
        return DB::transaction(function () use ($post): Post {
            $post->update([
                'status'       => PostStatus::Published,
                'published_at' => now(),
            ]);

            event(new PostPublished($post));
            NotifySubscribers::dispatch($post)->onQueue('notifications');

            Log::info('Post published', ['post_id' => $post->id]);

            return $post->fresh();
        });
    }

    public function delete(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            $post->tags()->detach();
            $post->delete(); // soft delete
        });
    }
}
```

---

## Dependency Injection & Container

Services are resolved via the container. Type-hint in constructors — no `new` keyword.

```php
// AppServiceProvider - bind interface to implementation
public function register(): void
{
    $this->app->bind(
        PostRepositoryInterface::class,
        EloquentPostRepository::class,
    );

    $this->app->singleton(
        SlackNotificationService::class,
        fn ($app) => new SlackNotificationService(
            config('services.slack.webhook_url')
        ),
    );

    $this->app->scoped(
        TenantManager::class,
        fn ($app) => new TenantManager($app->make(Request::class)),
    );
}
```

---

## Action Classes

For single-responsibility operations with no other dependencies. Good for complex, one-off tasks.

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateDraftPost
{
    public function execute(User $author, array $data): Post
    {
        return DB::transaction(function () use ($author, $data): Post {
            $post = $author->posts()->create([
                ...$data,
                'status' => PostStatus::Draft,
            ]);

            if (! empty($data['tags'])) {
                $post->tags()->attach($data['tags']);
            }

            return $post->load('tags');
        });
    }
}

// In controller:
// $post = $this->createDraftPost->execute($request->user(), $request->validated());
```

---

## Repository Pattern

Use only when you have multiple data sources or complex query logic worth abstracting.

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

interface PostRepositoryInterface
{
    public function findBySlug(string $slug): ?Post;
    public function findPublished(int $perPage): LengthAwarePaginator;
    public function create(array $data): Post;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPostRepository implements PostRepositoryInterface
{
    public function findBySlug(string $slug): ?Post
    {
        return Post::with(['author', 'tags'])
            ->where('slug', $slug)
            ->first();
    }

    public function findPublished(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['author:id,name', 'tags:id,name'])
            ->published()
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }
}
```

---

## Data Transfer Objects

Use PHP 8.3 readonly classes for DTOs. No mutations after construction.

```php
<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CreatePostData
{
    public function __construct(
        public string $title,
        public string $body,
        public int $userId,
        public PostStatus $status = PostStatus::Draft,
        public ?string $publishedAt = null,
        /** @var int[] */
        public array $tags = [],
    ) {}

    public static function fromRequest(StorePostRequest $request): self
    {
        return new self(
            title: $request->string('title'),
            body: $request->string('body'),
            userId: $request->user()->id,
            status: PostStatus::from($request->string('status', 'draft')),
            publishedAt: $request->input('published_at'),
            tags: $request->input('tags', []),
        );
    }
}
```

---

## Transactions

```php
// Basic
DB::transaction(function (): void {
    // all-or-nothing
});

// With retry on deadlock (3 attempts)
DB::transaction(function (): void {
    // ...
}, 3);

// Manual control
DB::beginTransaction();
try {
    // ...
    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

// After commit hook (fires only if transaction succeeds)
DB::afterCommit(function (): void {
    cache()->forget('posts.published');
    SlackAlert::dispatch('Post created');
});
```

---

## Service Provider Bindings

```php
// AppServiceProvider::boot()
public function boot(): void
{
    // Macro on query builder
    Builder::macro('whereLike', function (string $column, string $search): Builder {
        /** @var Builder $this */
        return $this->where($column, 'LIKE', "%{$search}%");
    });

    // Enforce strict mode in non-production
    Model::shouldBeStrict(! app()->isProduction());

    // Prevent lazy loading globally
    Model::preventLazyLoading(! app()->isProduction());

    // Prevent silently discarding fill
    Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
}
```
