# Routing, Controllers & Middleware Reference — Laravel 13

## Table of Contents
1. [Route Registration](#route-registration)
2. [API Resource Routes](#api-resource-routes)
3. [Route Model Binding](#route-model-binding)
4. [Controllers](#controllers)
5. [Form Requests](#form-requests)
6. [Middleware](#middleware)
7. [Response Patterns](#response-patterns)

---

## Route Registration

```php
// routes/api.php
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    // Public
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');

    // Protected
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('posts', PostController::class)->except(['index', 'show']);
        Route::post('posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
        Route::apiResource('posts.comments', CommentController::class)->shallow();
    });
});
```

---

## API Resource Routes

`apiResource` maps to: `index`, `store`, `show`, `update`, `destroy`

```bash
php artisan route:list --path=api/v1/posts

# GET    /api/v1/posts           posts.index
# POST   /api/v1/posts           posts.store
# GET    /api/v1/posts/{post}    posts.show
# PUT    /api/v1/posts/{post}    posts.update
# PATCH  /api/v1/posts/{post}    posts.update
# DELETE /api/v1/posts/{post}    posts.destroy
```

---

## Route Model Binding

```php
// Implicit — binds by {post} -> Post::findOrFail($id)
Route::get('posts/{post}', [PostController::class, 'show']);

// Bind by slug instead of id
// In Post model:
public function getRouteKeyName(): string
{
    return 'slug';
}

// Scoped binding — comment must belong to post
Route::get('posts/{post}/comments/{comment}', [CommentController::class, 'show'])
    ->scopeBindings();

// Explicit binding in AppServiceProvider
Route::model('post', Post::class);

// Custom resolution
Route::bind('post', fn (string $value) =>
    Post::where('slug', $value)->firstOrFail()
);
```

---

## Controllers

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Attributes\Middleware;

#[Middleware('auth:sanctum')]
final class PostController extends Controller
{
    public function __construct(private readonly PostService $postService) {}

    #[Middleware('throttle:60,1', only: ['index', 'show'])]
    public function index(): AnonymousResourceCollection
    {
        return PostResource::collection(
            $this->postService->paginate()
        );
    }

    public function show(Post $post): PostResource
    {
        return new PostResource(
            $post->loadMissing(['author', 'tags', 'comments'])
        );
    }

    public function store(StorePostRequest $request): PostResource
    {
        $post = $this->postService->create($request->validated());

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $post = $this->postService->update($post, $request->validated());

        return new PostResource($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->postService->delete($post);

        return response()->json(null, 204);
    }

    public function publish(Post $post): PostResource
    {
        $this->authorize('publish', $post);
        $post = $this->postService->publish($post);

        return new PostResource($post);
    }
}
```

---

## Form Requests

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Post::class);
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'body'         => ['required', 'string', 'min:10'],
            'status'       => ['sometimes', Rule::enum(PostStatus::class)],
            'category_id'  => ['required', 'integer', Rule::exists('categories', 'id')],
            'tags'         => ['sometimes', 'array', 'max:10'],
            'tags.*'       => ['integer', Rule::exists('tags', 'id')],
            'published_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The post title is required.',
            'body.min'       => 'The post body must be at least :min characters.',
        ];
    }

    // Auto-prepend computed values before validation
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => str($this->title)->slug(),
        ]);
    }

    // After validation hook
    protected function passedValidation(): void
    {
        // Anything that should happen only if validation passes
    }
}
```

---

## Middleware

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->belongsToTenant($request->route('tenant'))) {
            abort(403, 'Access denied to this tenant.');
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'tenant' => EnsureTenantAccess::class,
    ]);

    $middleware->appendToGroup('api', [
        ThrottleRequests::class.':api',
    ]);
})
```

---

## Response Patterns

```php
// 200 OK with resource
return new PostResource($post);

// 201 Created
return (new PostResource($post))->response()->setStatusCode(201);

// 204 No Content
return response()->json(null, 204);

// 422 Validation error (automatic from FormRequest)

// Custom JSON error
return response()->json([
    'message' => 'The given data was invalid.',
    'errors'  => ['field' => ['Error message']],
], 422);

// Paginated collection
return PostResource::collection(Post::paginate(15));
// Adds meta.current_page, meta.last_page, links.next, etc.
```
