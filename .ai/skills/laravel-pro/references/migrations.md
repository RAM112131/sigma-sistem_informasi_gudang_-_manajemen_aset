# Migrations, Schema & Database Reference — Laravel 13

## Table of Contents
1. [Migration Structure](#migration-structure)
2. [Column Types](#column-types)
3. [Indexes & Constraints](#indexes--constraints)
4. [Foreign Keys](#foreign-keys)
5. [Modifying Columns](#modifying-columns)
6. [Seeders & Factories](#seeders--factories)

---

## Migration Structure

```bash
php artisan make:migration create_posts_table
php artisan make:migration add_views_count_to_posts_table
php artisan make:migration create_post_tag_table  # pivot
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('views_count')->default(0);
            $table->decimal('price', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Composite index for frequent query pattern
            $table->index(['status', 'published_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

---

## Column Types

```php
// Strings
$table->string('name', 100);          // VARCHAR
$table->char('code', 4);              // CHAR
$table->text('body');                 // TEXT
$table->mediumText('content');
$table->longText('html');
$table->tinyText('excerpt');

// Numbers
$table->id();                          // BIGINT UNSIGNED AUTO_INCREMENT PK
$table->tinyInteger('sort_order');
$table->smallInteger('quantity');
$table->integer('count');
$table->bigInteger('large_number');
$table->unsignedBigInteger('views');
$table->float('rating', 3, 1);
$table->decimal('price', 10, 2);
$table->double('score', 8, 4);

// Boolean & Enum
$table->boolean('is_active')->default(true);
$table->enum('status', ['draft', 'published', 'archived'])->default('draft');
// Prefer string + PHP enum cast over DB enum

// Dates & Times
$table->date('birth_date');
$table->time('open_at');
$table->datetime('starts_at');
$table->timestamp('verified_at')->nullable();
$table->timestamps();         // created_at + updated_at
$table->softDeletes();        // deleted_at
$table->year('founded_in');

// JSON & Binary
$table->json('settings');
$table->jsonb('data');        // PostgreSQL only — prefer for indexed JSON
$table->binary('token');
$table->uuid('uuid')->primary();

// Spatial (PostgreSQL + pgvector for L13 semantic search)
$table->vector('embedding', 1536);  // pgvector column
```

---

## Indexes & Constraints

```php
$table->unique('email');
$table->unique(['tenant_id', 'email']);       // composite unique
$table->index('status');
$table->index(['status', 'created_at']);      // composite index
$table->fullText('body');                     // MySQL FULLTEXT
$table->spatialIndex('coordinates');          // spatial index
```

---

## Foreign Keys

```php
// Shorthand (recommended)
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('parent_id')->nullable()->constrained('posts')->cascadeOnDelete();

// Explicit
$table->unsignedBigInteger('author_id');
$table->foreign('author_id')
    ->references('id')
    ->on('users')
    ->cascadeOnDelete()
    ->cascadeOnUpdate();
```

**L13 Note**: `ORDER BY` / `LIMIT` in `DELETE` with `JOIN` are now included in generated SQL.
MySQL/MariaDB may throw a `QueryException` on unsupported engines — test your delete logic.

---

## Pivot Table

```php
Schema::create('post_tag', function (Blueprint $table): void {
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->unsignedTinyInteger('order')->default(0);
    $table->timestamps();

    $table->primary(['post_id', 'tag_id']);
});
```

**L13 Note**: Polymorphic pivot models now use pluralized table names by default.
If you have custom morph pivot models with inferred table names, check them after upgrading.

---

## Modifying Columns

```php
// Always requires doctrine/dbal for pre-L10, native in L10+
Schema::table('posts', function (Blueprint $table): void {
    $table->string('title', 255)->change();
    $table->string('slug')->nullable()->change();
    $table->renameColumn('body', 'content');
    $table->dropColumn('legacy_field');
    $table->dropSoftDeletes();
    $table->after('title', function (Blueprint $table): void {
        $table->string('subtitle')->nullable();
    });
});
```

---

## Seeders & Factories

### Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
final class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'title'        => fake()->sentence(6),
            'slug'         => fake()->unique()->slug(),
            'body'         => fake()->paragraphs(5, true),
            'status'       => PostStatus::Draft,
            'views_count'  => fake()->numberBetween(0, 10000),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attrs) => [
            'status'       => PostStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
```

### Seeder

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

final class PostSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(10)->create();

        $tags = Tag::factory(20)->create();

        $users->each(function (User $user) use ($tags): void {
            Post::factory(5)
                ->forUser($user)
                ->published()
                ->create()
                ->each(function (Post $post) use ($tags): void {
                    $post->tags()->attach($tags->random(3)->pluck('id'));
                });
        });
    }
}
```
