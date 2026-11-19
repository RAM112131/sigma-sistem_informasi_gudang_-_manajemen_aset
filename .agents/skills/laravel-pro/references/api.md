# API Resources & JSON:API Reference — Laravel 13

## Table of Contents

1. [Standard JSON API Resources](#standard-json-api-resources)
2. [Collection Resources](#collection-resources)
3. [Conditional Loading](#conditional-loading)
4. [JSON:API Resources (L13)](#jsonapi-resources-l13)
5. [API Versioning](#api-versioning)
6. [Error Responses](#error-responses)

---

## Standard JSON API Resources

```bash
php artisan make:resource PostResource
php artisan make:resource PostCollection  # optional, for collection customization
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => 'post',
            'title'        => $this->title,
            'slug'         => $this->slug,
            'body'         => $this->body,
            'status'       => $this->status->value,
            'views_count'  => $this->views_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at'   => $this->created_at->toIso8601String(),

            // Conditionally include relationships
            'author'    => new UserResource($this->whenLoaded('author')),
            'tags'      => TagResource::collection($this->whenLoaded('tags')),
            'comments'  => CommentResource::collection($this->whenLoaded('comments')),

            // Conditional fields
            'body_html' => $this->when(
                $request->boolean('include_html'),
                fn () => str($this->body)->markdown()->toString(),
            ),

            // Merge only for authenticated user who is the author
            $this->mergeWhen(
                $request->user()?->id === $this->user_id,
                ['draft_notes' => $this->draft_notes]
            ),

            // Pivot data
            'pivot_order' => $this->whenPivotLoaded('post_tag', fn () =>
                $this->pivot->order
            ),
        ];
    }

    // Add extra top-level keys
    public function with(Request $request): array
    {
        return [
            'meta' => ['version' => 'v1'],
        ];
    }
}
```

---

## Collection Resources

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class PostCollection extends ResourceCollection
{
    public $collects = PostResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version'  => 'v1',
                'filters'  => $request->only(['status', 'author_id']),
            ],
        ];
    }
}
```

Usage in controller:

```php
return PostResource::collection(Post::with(['author', 'tags'])->paginate(15));
// or
return new PostCollection(Post::with(['author', 'tags'])->paginate(15));
```

---

## Conditional Loading

```php
// Merge conditionals
$this->mergeWhen($condition, ['key' => 'value']);

// Single value
$this->when($condition, $value, $fallback);

// Relationship — null if not loaded, omitted key if no whenLoaded
'author' => new UserResource($this->whenLoaded('author')),

// whenLoaded with default
'author' => $this->whenLoaded('author', fn () =>
    new UserResource($this->author),
    fn () => ['id' => $this->user_id],
),

// Count
'comments_count' => $this->whenCounted('comments'),

// Aggregates
'average_rating' => $this->whenAggregated('reviews', 'rating', 'avg'),
```

---

## JSON:API Resources (L13)

Laravel 13 adds first-party JSON:API spec support via `JsonApiResource`.

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonApi\JsonApiResource;

final class PostJsonApiResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'title'        => $this->title,
            'body'         => $this->body,
            'status'       => $this->status->value,
            'published-at' => $this->published_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'author'   => fn () => new UserJsonApiResource($this->author),
            'tags'     => fn () => TagJsonApiResource::collection($this->tags),
        ];
    }

    public function toLinks(Request $request): array
    {
        return [
            'self' => route('api.v1.posts.show', $this->id),
        ];
    }
}
```

Returns:
```json
{
  "data": {
    "type": "posts",
    "id": "1",
    "attributes": { "title": "...", "status": "published" },
    "relationships": {
      "author": { "data": { "type": "users", "id": "5" } }
    },
    "links": { "self": "https://example.com/api/v1/posts/1" }
  }
}
```

---

## API Versioning

Namespace by version directory:

```
app/Http/
  Controllers/
    Api/
      V1/
        PostController.php
      V2/
        PostController.php
  Resources/
    V1/
      PostResource.php
    V2/
      PostResource.php
  Requests/
    V1/
      StorePostRequest.php
```

```php
// routes/api.php
Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::apiResource('posts', V1\PostController::class);
});

Route::prefix('v2')->name('api.v2.')->group(function (): void {
    Route::apiResource('posts', V2\PostController::class);
});
```

---

## Error Responses

Handle globally in `bootstrap/app.php`:

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (ModelNotFoundException $e, Request $request): ?Response {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Resource not found.',
            ], 404);
        }
        return null;
    });

    $exceptions->render(function (AuthorizationException $e, Request $request): ?Response {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        }
        return null;
    });

    $exceptions->render(function (ValidationException $e, Request $request): ?Response {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        }
        return null;
    });
})
```

Standard error shape:
```json
{
  "message": "Human-readable summary.",
  "errors": {
    "field": ["Validation message."]
  }
}
```
