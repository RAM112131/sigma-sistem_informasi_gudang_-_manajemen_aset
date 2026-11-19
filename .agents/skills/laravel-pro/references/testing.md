# Testing Reference — Laravel 13 (Pest PHP)

## Table of Contents

1. [Test Structure](#test-structure)
2. [Feature Tests — API](#feature-tests--api)
3. [Database & Factories](#database--factories)
4. [Mocking & Faking](#mocking--faking)
5. [Dataset-Driven Tests](#dataset-driven-tests)
6. [Custom Expectations](#custom-expectations)
7. [Coverage & Running](#coverage--running)

---

## Test Structure

```
tests/
├── Feature/
│   ├── Api/
│   │   └── V1/
│   │       ├── PostControllerTest.php
│   │       └── AuthControllerTest.php
│   └── Services/
│       └── PostServiceTest.php
├── Unit/
│   └── Models/
│       └── PostTest.php
├── Pest.php      ← global helpers, uses(), custom expectations
└── TestCase.php
```

---

## Test Structure & Pest.php

```php
// tests/Pest.php
<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

// Global helpers
function actingAsUser(?User $user = null): User
{
    $user ??= User::factory()->create();
    test()->actingAs($user);
    return $user;
}

function actingAsAdmin(): User
{
    $user = User::factory()->admin()->create();
    test()->actingAs($user);
    return $user;
}
```

---

## Feature Tests — API

```php
<?php

declare(strict_types=1);

use App\Jobs\ProcessPostPublish;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('PostController', function (): void {

    describe('GET /api/v1/posts', function (): void {

        it('returns paginated published posts for guests', function (): void {
            Post::factory(5)->published()->create();
            Post::factory(3)->create(); // drafts — should not appear

            $response = $this->getJson('/api/v1/posts');

            $response->assertOk()
                ->assertJsonCount(5, 'data')
                ->assertJsonStructure([
                    'data' => [['id', 'title', 'status', 'author']],
                    'links' => ['next', 'prev'],
                    'meta'  => ['current_page', 'total'],
                ]);
        });

        it('returns 15 posts per page by default', function (): void {
            Post::factory(20)->published()->create();

            $this->getJson('/api/v1/posts')
                ->assertOk()
                ->assertJsonCount(15, 'data');
        });

    });

    describe('POST /api/v1/posts', function (): void {

        it('creates a post and returns 201', function (): void {
            $user = actingAsUser();

            $response = $this->postJson('/api/v1/posts', [
                'title' => 'Test Post',
                'body'  => 'This is the post body with enough content.',
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.title', 'Test Post')
                ->assertJsonPath('data.status', 'draft');

            $this->assertDatabaseHas('posts', [
                'title'   => 'Test Post',
                'user_id' => $user->id,
            ]);
        });

        it('returns 422 when title is missing', function (): void {
            actingAsUser();

            $this->postJson('/api/v1/posts', ['body' => 'Some body content.'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['title']);
        });

        it('returns 401 for unauthenticated requests', function (): void {
            $this->postJson('/api/v1/posts', ['title' => 'Test'])
                ->assertUnauthorized();
        });

    });

    describe('POST /api/v1/posts/{post}/publish', function (): void {

        it('dispatches publish job and returns 200', function (): void {
            Queue::fake();
            $user = actingAsUser();
            $post = Post::factory()->draft()->for($user, 'author')->create();

            $this->postJson("/api/v1/posts/{$post->id}/publish")
                ->assertOk();

            Queue::assertPushed(ProcessPostPublish::class, function ($job) use ($post): bool {
                return $job->post->is($post);
            });
        });

        it('returns 403 when user is not the author', function (): void {
            actingAsUser();
            $post = Post::factory()->draft()->create(); // another user's post

            $this->postJson("/api/v1/posts/{$post->id}/publish")
                ->assertForbidden();
        });

    });

});
```

---

## Database & Factories

```php
// Use RefreshDatabase — wraps in transaction, rolls back after each test
uses(RefreshDatabase::class);

// Create models
$user = User::factory()->create();
$user = User::factory()->admin()->withSubscription()->create();
$posts = Post::factory(5)->published()->for($user, 'author')->create();

// Assert database state
$this->assertDatabaseHas('posts', ['title' => 'Test', 'user_id' => $user->id]);
$this->assertDatabaseMissing('posts', ['id' => $post->id, 'deleted_at' => null]);
$this->assertDatabaseCount('posts', 5);
$this->assertSoftDeleted('posts', ['id' => $post->id]);

// With relationships
$post = Post::factory()
    ->has(Comment::factory(3), 'comments')
    ->for($user, 'author')
    ->create();
```

---

## Mocking & Faking

```php
// Queue
Queue::fake();
Queue::assertPushed(ProcessPostPublish::class);
Queue::assertPushed(ProcessPostPublish::class, 2); // exactly 2 times
Queue::assertNotPushed(ProcessPostPublish::class);
Queue::assertPushedOn('high', ProcessPostPublish::class);

// Events
Event::fake();
Event::assertDispatched(PostPublished::class);
Event::assertDispatched(PostPublished::class, fn ($e) => $e->post->is($post));
Event::assertNotDispatched(PostPublished::class);

// Mail
Mail::fake();
Mail::assertSent(WelcomeMail::class);
Mail::assertSent(WelcomeMail::class, fn ($m) => $m->hasTo($user->email));

// Notifications
Notification::fake();
Notification::assertSentTo($user, PostPublishedNotification::class);
Notification::assertNothingSent();

// HTTP Client (external APIs)
Http::fake([
    'api.openai.com/*' => Http::response(['choices' => [['text' => 'AI response']]], 200),
    '*'                => Http::response(['error' => 'unexpected'], 500),
]);
Http::assertSent(fn ($req) => $req->url() === 'https://api.openai.com/v1/completions');

// Storage
Storage::fake('s3');
Storage::disk('s3')->assertExists('images/post-1.jpg');
```

---

## Dataset-Driven Tests

```php
it('validates post status values', function (string $status, bool $valid): void {
    actingAsUser();

    $response = $this->postJson('/api/v1/posts', [
        'title'  => 'Test',
        'body'   => 'Body content here.',
        'status' => $status,
    ]);

    if ($valid) {
        $response->assertCreated();
    } else {
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }
})->with([
    'draft is valid'     => ['draft', true],
    'published is valid' => ['published', true],
    'invalid status'     => ['spam', false],
    'empty string'       => ['', false],
]);
```

---

## Custom Expectations

```php
// tests/Pest.php
expect()->extend('toBePublished', function (): Expectation {
    return $this->toBeInstanceOf(Post::class)
        ->and($this->value->status)->toBe(PostStatus::Published)
        ->and($this->value->published_at)->not->toBeNull();
});

expect()->extend('toBeValidApiResponse', function (int $status = 200): Expectation {
    $this->value->assertStatus($status)
        ->assertJsonStructure(['data'])
        ->assertHeader('Content-Type', 'application/json');
    return $this;
});

// Usage
expect($post)->toBePublished();
expect($response)->toBeValidApiResponse(201);
```

---

## Coverage & Running

```bash
# Run all tests
php artisan test

# Run specific filter
php artisan test --filter=PostControllerTest

# Run a single test file
php artisan test tests/Feature/Api/V1/PostControllerTest.php

# Coverage report (requires Xdebug or PCOV)
php artisan test --coverage
php artisan test --coverage --min=85   # fail if below 85%

# Parallel (faster on multi-core)
php artisan test --parallel

# Stop on first failure
php artisan test --stop-on-failure

# Show slow tests
php artisan test --profile
```
