# Eloquent ORM Reference — Laravel 13

## Table of Contents

1. [Model Conventions](#model-conventions)
2. [PHP Attribute Syntax (L13)](#php-attribute-syntax-l13)
3. [Fillable & Casts](#fillable--casts)
4. [Relationships](#relationships)
5. [Query Scopes](#query-scopes)
6. [Accessors & Mutators](#accessors--mutators)
7. [Observers](#observers)
8. [Global Scopes](#global-scopes)
9. [Query Optimization](#query-optimization)
10. [Model Events](#model-events)

---

## Model Conventions

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Post extends Model
{
    use HasFactory, SoftDeletes;

    // Only set these when deviating from Laravel conventions
    // protected $table = 'posts';
    // protected $primaryKey = 'id';
    // public $timestamps = true;
    // public $incrementing = true;
    // protected $keyType = 'int';
}
```

**L13 Breaking Change**: Never instantiate a model inside `boot()` or any `boot*()` trait method.
It throws `LogicException`. Move such logic to a service provider or after boot.

---

## PHP Attribute Syntax (L13)

Laravel 13 introduces optional PHP attribute syntax as an alternative to class property declarations.
These are fully backward compatible — the old property style still works.

```php
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

// Old style
// protected static string $observer = PostObserver::class;
// protected static string $scope = ActiveScope::class;

// L13 attribute style
#[ObservedBy(PostObserver::class)]
#[ScopedBy(ActiveScope::class)]
final class Post extends Model { ... }
```

Available model attributes: `#[ObservedBy]`, `#[ScopedBy]`, `#[UseFactory]`, `#[UsePolicy]`

Controller attributes: `#[Middleware]`, `#[Authorize]`

Job attributes: `#[WithoutOverlapping]`, `#[OnQueue]`, `#[OnConnection]`

---

## Fillable & Casts

```php
protected $fillable = [
    'title', 'body', 'status', 'user_id', 'published_at',
];

// Never use $guarded = [] in production

protected $casts = [
    'status'       => PostStatus::class,          // backed enum
    'published_at' => 'immutable_datetime',        // returns CarbonImmutable
    'metadata'     => 'array',                     // JSON column -> PHP array
    'settings'     => AsArrayObject::class,        // JSON with dot-notation access
    'tags'         => AsCollection::class,         // JSON -> Collection
    'is_featured'  => 'boolean',
    'price'        => 'decimal:2',
    'score'        => 'float',
];

// Enums — backed enum only
enum PostStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';
}
```

---

## Relationships

### One-to-One

```php
public function profile(): HasOne
{
    return $this->hasOne(Profile::class);
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

### One-to-Many

```php
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}

public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

### Many-to-Many

```php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class)
        ->withPivot(['order', 'created_at'])
        ->withTimestamps()
        ->orderByPivot('order');
}
```

### Has-Many-Through

```php
// User -> Post -> Comment
public function comments(): HasManyThrough
{
    return $this->hasManyThrough(Comment::class, Post::class);
}
```

### Polymorphic

```php
// On Image model
public function imageable(): MorphTo
{
    return $this->morphTo();
}

// On Post / Product model
public function images(): MorphMany
{
    return $this->morphMany(Image::class, 'imageable');
}
```

### Eager Loading — Always at call site

```php
// Good — eager load in service/controller
Post::with(['author', 'tags', 'comments.author'])
    ->where('status', PostStatus::Published)
    ->latest()
    ->paginate(15);

// Good — conditional eager loading
Post::with(['author' => fn ($q) => $q->select(['id', 'name', 'email'])])
    ->paginate(15);

// Never — lazy loading causes N+1
foreach ($posts as $post) {
    echo $post->author->name; // BAD if author not eager-loaded
}
```

---

## Query Scopes

### Local Scopes

```php
public function scopePublished(Builder $query): Builder
{
    return $query->where('status', PostStatus::Published);
}

public function scopeByAuthor(Builder $query, int $userId): Builder
{
    return $query->where('user_id', $userId);
}

// Usage
Post::published()->byAuthor($userId)->latest()->get();
```

### Global Scope (class-based)

```php
<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class ActiveScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('is_active', true);
    }
}
```

Attach with `#[ScopedBy(ActiveScope::class)]` or in `booted()`:

```php
protected static function booted(): void
{
    static::addGlobalScope(new ActiveScope);
}
```

Remove when needed: `Post::withoutGlobalScope(ActiveScope::class)->get()`

---

## Accessors & Mutators

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

// L9+ style — preferred
protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => "{$this->first_name} {$this->last_name}",
        set: fn (string $value) => [
            'first_name' => explode(' ', $value)[0],
            'last_name'  => explode(' ', $value)[1] ?? '',
        ],
    )->shouldCache();
}

// Access: $user->full_name
```

---

## Observers

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Post;

final class PostObserver
{
    public function creating(Post $post): void
    {
        $post->slug = str($post->title)->slug();
    }

    public function deleting(Post $post): void
    {
        $post->images()->delete();
    }
}
```

Register with `#[ObservedBy(PostObserver::class)]` on model or in `AppServiceProvider`.

---

## Query Optimization

```php
// Select only needed columns
Post::select(['id', 'title', 'user_id', 'status'])
    ->with(['author:id,name'])
    ->paginate(15);

// Chunk for large datasets — never get() on large tables
Post::with('tags')->chunk(200, function (Collection $posts): void {
    foreach ($posts as $post) {
        // process
    }
});

// Lazy collections for memory-efficient streaming
Post::lazy()->each(function (Post $post): void {
    // one model in memory at a time
});

// whereExists vs whereIn — whereExists is usually faster for subqueries
Post::whereExists(function (Builder $query): void {
    $query->select(DB::raw(1))
        ->from('comments')
        ->whereColumn('comments.post_id', 'posts.id');
})->get();

// Cache expensive queries
$popularPosts = Cache::remember('popular-posts', 3600, fn () =>
    Post::published()
        ->orderByDesc('views_count')
        ->limit(10)
        ->get()
);

// Cache::touch() — L13, extend TTL without re-fetching
Cache::touch('popular-posts', 3600);
```

---

## Model Events

Order: `creating` → `created` → `updating` → `updated` → `saving` → `saved` → `deleting` → `deleted`

Use observers for side effects. Use `$dispatchesEvents` for explicit event mapping:

```php
protected $dispatchesEvents = [
    'created' => PostCreated::class,
    'deleted' => PostDeleted::class,
];
```
