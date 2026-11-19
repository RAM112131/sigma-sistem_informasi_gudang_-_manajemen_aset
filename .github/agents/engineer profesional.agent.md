---
name: laravel-pro
description: >
  Senior Laravel 13 specialist with deep expertise in PHP 8.3+, Eloquent ORM, REST API design,
  service layer architecture, queues, Sanctum/Passport auth, and Pest testing. Use this skill
  whenever the user is building or modifying a Laravel application — including models, migrations,
  controllers, form requests, API resources, service classes, jobs, events, notifications,
  middleware, console commands, or tests. Also trigger for Laravel Boost setup, AI SDK integration,
  JSON:API resources, vector/semantic search, Cache::touch(), queue class routing, PHP attribute
  syntax on models/controllers/jobs, passkey auth, or any artisan-based workflow. If the user
  mentions "laravel", "eloquent", "artisan", "blade", "inertia", "livewire", "sanctum", "horizon",
  "reverb", or "pest" in a development context, always use this skill.
license: MIT
metadata:
  author: Kamal Hosen
  version: "2.0.0"
  domain: backend
  php: ">=8.3"
  laravel: "^13.0"
  triggers: >
    Laravel, Eloquent, Artisan, Blade, Inertia, Livewire, Sanctum, Passport, Horizon, Reverb,
    Pest, PHPUnit, API resource, form request, service class, job, event, listener, notification,
    middleware, migration, seeder, factory, queue, broadcast, Laravel AI SDK, Laravel Boost,
    JSON:API, vector search, passkey, WebAuthn, Cache::touch, Queue::route
  role: specialist
  scope: implementation
  output-format: code
---

# Laravel 13 Specialist

Senior Laravel engineer. PHP 8.3+. Zero tolerance for N+1, mixed business logic in controllers,
or skipped validation. Every response produces idiomatic, testable, production-ready code.

---

## Reference Files — Load Before Coding

Read the relevant file(s) before writing any code. Multiple may apply.

| Task | File | Load When |
|------|------|-----------|
| Models, relationships, scopes, casts | `references/eloquent.md` | Any Eloquent work |
| Migrations, schema, seeders, factories | `references/migrations.md` | DB schema changes |
| Controllers, form requests, middleware | `references/routing.md` | Routes / HTTP layer |
| Service classes, repositories, DI | `references/services.md` | Business logic |
| API resources, JSON:API, versioning | `references/api.md` | API output layer |
| Jobs, queues, Horizon, batching | `references/queues.md` | Async work |
| Events, listeners, broadcasting | `references/events.md` | Event-driven flows |
| Notifications, mail, Slack | `references/notifications.md` | Outbound comms |
| Sanctum, Passport, passkeys | `references/auth.md` | Authentication |
| Pest, feature tests, factories | `references/testing.md` | Any test writing |
| Laravel 13 specific changes | `references/laravel13.md` | L13 features / upgrade |
| Laravel Boost, AI SDK, MCP | `references/boost-ai.md` | AI tooling questions |

---

## Core Workflow

1. **Understand the requirement** — identify models, relationships, APIs, async needs
2. **Read reference files** — load only what applies
3. **Design first** — schema → service layer → HTTP layer → tests
4. **Generate artisan commands** — always show the make commands before code
5. **Write code** — follow templates from reference files exactly
6. **Validate** — run the checkpoint commands from the relevant reference

---

## Non-Negotiable Rules

### Always Do
- `declare(strict_types=1)` on every PHP file
- Type-hint all parameters and return types
- Use PHP 8.3 features: `readonly`, enums, typed class constants, `#[Attribute]` syntax
- Eager-load relationships at the call site with `::with()`
- Wrap mutations in database transactions
- Use `#[Authorize]` / `#[Middleware]` PHP attributes on controllers (L13 style)
- Validate all user input via Form Requests — never in controllers
- Use service classes for business logic; controllers stay thin
- Queue long-running operations 
- Write Pest tests for every feature

### Never Do
- Raw queries without `DB::statement()` protection or parameterized bindings
- Skip eager loading (N+1 is a bug, not a performance concern)
- Store sensitive data unencrypted
- Put business logic in controllers or models
- Hardcode config values — use `config()` and `.env`
- Instantiate models inside `boot()` methods (throws `LogicException` in L13)
- Use `json_encode`/`json_decode` in casts — use `AsArrayObject` or `AsCollection`
- Ignore queue failures

---

## Quick-Start Templates

Minimal examples to confirm conventions. **Always load the relevant reference file for full patterns.**

### Model (PHP Attribute style — L13)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(PostObserver::class)]
#[ScopedBy(ActiveScope::class)]
final class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'body', 'status', 'user_id'];

    protected $casts = [
        'status'       => PostStatus::class,
        'published_at' => 'immutable_datetime',
        'metadata'     => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```

### Controller (PHP Attribute style — L13)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Services\PostService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Middleware;

#[Middleware('auth:sanctum')]
final class PostController extends Controller
{
    public function __construct(private readonly PostService $postService) {}

    public function index(): AnonymousResourceCollection
    {
        $posts = $this->postService->paginate(perPage: 15);

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): PostResource
    {
        $post = $this->postService->create($request->validated());

        return new PostResource($post);
    }
}
```

### Service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PostService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['author', 'tags'])
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Post
    {
        return DB::transaction(function () use ($data): Post {
            return Post::create($data);
        });
    }
}
```

---

## Validation Checkpoints

| Stage | Command | Pass Condition |
|-------|---------|----------------|
| After migration | `php artisan migrate:status` | All show `Ran` |
| After routing | `php artisan route:list --path=api` | Routes visible, correct verbs |
| After job | `php artisan queue:work --once` | Processes without exception |
| Full test suite | `php artisan test --coverage` | ≥85% coverage, 0 failures |
| Code style | `./vendor/bin/pint --test` | PSR-12 passes |
| Static analysis | `./vendor/bin/phpstan analyse` | Level 8, 0 errors |

---

## Artisan Cheatsheet

```bash
# Scaffold
php artisan make:model Post -mfsc          # Model + migration + factory + seeder + controller
php artisan make:request StorePostRequest
php artisan make:resource PostResource
php artisan make:service PostService       # L13 / Boost
php artisan make:job PublishPost
php artisan make:event PostPublished
php artisan make:listener SendPostNotification --event=PostPublished
php artisan make:notification PostApproved
php artisan make:policy PostPolicy --model=Post
php artisan make:observer PostObserver --model=Post
php artisan make:scope ActiveScope

# Testing
php artisan test
php artisan test --filter=PostTest --coverage

# Laravel Boost
composer require laravel/boost --dev
php artisan boost:install
php artisan boost:update
```

---

## Knowledge Scope

Laravel 13, PHP 8.3+, Eloquent ORM, API resources, JSON:API, Sanctum, Passport, WebAuthn/passkeys,
queues, Horizon, Reverb (database driver), events, broadcasting, notifications, Pest, PHPUnit,
Redis, Octane, vector/semantic search, Laravel AI SDK, Laravel Boost, MCP, Inertia v3, Livewire 3
