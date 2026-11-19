# Laravel 13 — Changes, New Features & Upgrade Notes

Released: **March 17, 2026** at Laracon EU. PHP 8.3+ required.

## Table of Contents

1. [Breaking Changes](#breaking-changes)
2. [PHP Attribute Syntax](#php-attribute-syntax)
3. [JSON:API Resources](#jsonapi-resources)
4. [Queue Class Routing](#queue-class-routing)
5. [Cache Improvements](#cache-improvements)
6. [Vector / Semantic Search](#vector--semantic-search)
7. [Laravel AI SDK](#laravel-ai-sdk)
8. [Passkeys / WebAuthn](#passkeys--webauthn)
9. [Reverb Database Driver](#reverb-database-driver)
10. [PreventRequestForgery](#preventrequestforgery)
11. [Upgrade Checklist](#upgrade-checklist)

---

## Breaking Changes

### PHP 8.3 Required

PHP 8.2 support dropped. PHP 8.3 and 8.5 both supported.

### Model Booting

Instantiating a model inside `boot()` or `boot*()` trait methods now throws `LogicException`:

```php
// Breaks in L13
protected static function boot(): void
{
    parent::boot();
    (new static())->getTable(); // LogicException
}

// Fix — move to booted() or a service provider
protected static function booted(): void
{
    // safe
}
```

### DELETE with JOIN / ORDER BY / LIMIT

MySQL/MariaDB may throw `QueryException` on `DELETE` statements that now include these clauses.
Audit any `->delete()` calls on query builders that also have `->join()`, `->orderBy()`, or `->limit()`.

### Polymorphic Pivot Table Names

Custom morph pivot model classes now generate pluralized table names. Check and correct any affected pivot tables.

### Cache Prefixes

```php
// L12
Str::slug(env('APP_NAME'), '_').'_cache_'

// L13 — uses hyphens now
Str::slug(env('APP_NAME')).'-cache-'
```

Set `CACHE_PREFIX`, `REDIS_PREFIX`, and `SESSION_COOKIE` explicitly to preserve old behavior across upgrades.

### Cache Serialization

`serializable_classes` now defaults to `false` in cache config — hardens against deserialization gadget attacks. If you store PHP objects in cache, you must explicitly allow the classes:

```php
// config/cache.php
'serializable_classes' => [
    MyValueObject::class,
    AnotherClass::class,
],
```

### Pagination View Names

```php
// L12
pagination::default
pagination::simple-default

// L13
pagination::bootstrap-3
pagination::simple-bootstrap-3
```

Update any direct `@include('pagination::default')` references.

---

## PHP Attribute Syntax

Over 15 locations where PHP attributes can replace class property declarations. All optional.

```php
// Models
#[ObservedBy(PostObserver::class)]
#[ScopedBy(ActiveScope::class)]
#[UseFactory(PostFactory::class)]
#[UsePolicy(PostPolicy::class)]
final class Post extends Model { ... }

// Controllers
#[Middleware('auth:sanctum')]
#[Middleware('throttle:60,1', only: ['index'])]
final class PostController extends Controller { ... }

// Methods
#[Authorize(PostPolicy::class, 'update')]
public function update(Request $request, Post $post): PostResource { ... }

// Jobs
#[WithoutOverlapping]
#[OnQueue('high')]
#[OnConnection('redis')]
final class ProcessPostPublish implements ShouldQueue { ... }

// Commands
#[Schedule(expression: '0 * * * *')]
#[Isolatable]
final class CleanupDraftPosts extends Command { ... }

// Listeners
#[ListenTo(PostPublished::class)]
final class SendPostNotification implements ShouldQueue { ... }
```

---

## JSON:API Resources

First-party JSON:API specification support.

```php
use Illuminate\Http\Resources\Json\JsonApi\JsonApiResource;

final class PostJsonApiResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return ['title' => $this->title, 'status' => $this->status->value];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'author' => fn () => new UserJsonApiResource($this->author),
        ];
    }

    public function toLinks(Request $request): array
    {
        return ['self' => route('api.v1.posts.show', $this->id)];
    }
}
```

Handles: resource object serialization, relationship inclusion, sparse fieldsets, links, and `Content-Type: application/vnd.api+json` response headers automatically.

---

## Queue Class Routing

Define default queue/connection per job class without touching individual jobs.

```php
// AppServiceProvider::boot()
use Illuminate\Support\Facades\Queue;

Queue::route([
    ProcessPostPublish::class    => 'high',
    GenerateEmbedding::class     => 'ai',
    SendWelcomeEmail::class      => 'mail',
    NotifySubscribers::class     => 'notifications',
]);

// Or with connection:queue pair
Queue::route([
    ProcessPostPublish::class => 'redis:high',
]);
```

---

## Cache Improvements

### Cache::touch()

Extends TTL without fetching the value — single Redis EXPIRE / Memcached TOUCH command.

```php
Cache::touch('popular-posts', 3600);     // extend by 1 hour
Cache::touch('popular-posts');           // extend by default TTL

// Old approach (still works but slower)
$value = Cache::get('popular-posts');
Cache::put('popular-posts', $value, 3600);
```

### Custom Cache Store — touch() method required

If you implement a custom cache store, add `touch(string $key, int $seconds): bool`.

---

## Vector / Semantic Search

Native pgvector support in query builder. Requires PostgreSQL + pgvector extension.

```php
// Migration
$table->vector('embedding', 1536); // 1536 dimensions for OpenAI text-embedding-3-small

// Generate embedding (AI SDK)
use Laravel\Ai\Facades\AI;
$embedding = AI::embedding('Best wineries in Napa Valley');

// Store
Post::create([
    'title'     => '...',
    'embedding' => $embedding,
]);

// Query by similarity
$docs = DB::table('posts')
    ->whereVectorSimilarTo('embedding', 'Best wineries in Napa Valley')
    ->orderByVectorSimilarityTo('embedding', $embedding)
    ->limit(10)
    ->get();

// Eloquent
Post::whereVectorSimilarTo('embedding', $queryEmbedding)
    ->limit(5)
    ->get();
```

---

## Laravel AI SDK

First-party, provider-agnostic. Stable as of L13 release.

```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate
```

```php
// Text generation
use Laravel\Ai\Facades\AI;
$response = AI::text('Summarize this post: '.$post->body);
$summary = (string) $response;

// Structured agent
use App\Ai\Agents\ContentReviewAgent;
$result = ContentReviewAgent::make()->prompt("Review: {$post->body}");

// Embeddings
$embedding = AI::embedding($text);

// Image generation
use Laravel\Ai\Image;
$image = Image::of('A blog banner for: '.$post->title)->generate();

// Audio
use Laravel\Ai\Audio;
$audio = Audio::of($post->excerpt)->generate();

// Queue long-running AI tasks
AI::text($prompt)->queue(
    then: fn ($result) => $post->update(['summary' => $result]),
    catch: fn ($e) => Log::error('AI failed', ['error' => $e->getMessage()]),
);

// Failover — automatic when provider hits quota or billing error
// Configure providers in order in config/ai.php; SDK handles failover silently
```

Config:

```php
// config/ai.php
'providers' => [
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model'   => 'claude-sonnet-4-20250514',
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => 'gpt-4o',
    ],
],
'default' => env('AI_PROVIDER', 'anthropic'),
```

---

## Passkeys / WebAuthn

Built into L13 starter kits via Fortify. See `references/auth.md` for full setup.

Key points:
- Private key never leaves the device
- Resistant to phishing and credential stuffing
- Works with Face ID, fingerprint, Windows Hello, hardware keys
- `Features::passkeys()` feature flag in Fortify config

---

## Reverb Database Driver

Small/medium apps no longer need Redis for Reverb horizontal scaling.

```php
// config/broadcasting.php
'scaling' => [
    'enabled'    => true,
    'connection' => 'database',  // L13 addition — was Redis-only in L12
],
```

---

## PreventRequestForgery

CSRF middleware renamed and enhanced with origin verification.

Old name: `VerifyCsrfToken`
New name: `PreventRequestForgery`

Added origin-aware request verification — checks `Origin` header on state-mutating requests.
Review cross-origin subdomain setups after upgrading.

---

## Upgrade Checklist

1. **PHP 8.3+** — upgrade server
2. **`composer.json`** — bump `laravel/framework` to `^13.0`
3. **Model `boot()` methods** — no model instantiation inside them
4. **Cache prefixes** — set `CACHE_PREFIX`/`REDIS_PREFIX`/`SESSION_COOKIE` if using defaults
5. **Cache serialization** — add `serializable_classes` to `config/cache.php` if storing objects
6. **DELETE queries** — audit JOIN + ORDER BY + LIMIT delete patterns
7. **Polymorphic pivot models** — verify table names still match
8. **Pagination views** — update any direct view references
9. **Custom cache stores** — add `touch()` method
10. **CSRF / PreventRequestForgery** — review cross-origin subdomain setups
11. **Test suite** — `php artisan test` must pass with 0 failures

```bash
# Auto-upgrade via Boost (recommended)
composer require laravel/boost --dev
php artisan boost:install
# Use /upgrade-laravel-v13 slash command in Claude Code / Cursor
```
