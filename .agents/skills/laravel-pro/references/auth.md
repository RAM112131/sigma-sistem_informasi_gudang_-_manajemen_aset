# Authentication Reference — Laravel 13

## Table of Contents

1. [Sanctum (SPA / Mobile API)](#sanctum-spa--mobile-api)
2. [Passport (OAuth2 Server)](#passport-oauth2-server)
3. [WebAuthn / Passkeys (L13)](#webauthn--passkeys-l13)
4. [Authorization — Policies & Gates](#authorization--policies--gates)
5. [PreventRequestForgery (L13)](#preventrequestforgery-l13)

---

## Sanctum (SPA / Mobile API)

The default for Laravel APIs and mobile apps. Tokens stored in DB.

```bash
composer require laravel/sanctum
php artisan install:api     # installs Sanctum, creates api.php route file
php artisan migrate
```

```php
// User model
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

### Login — issue token

```php
final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();
        $user->tokens()->where('name', 'mobile')->delete(); // revoke old

        $token = $user->createToken('mobile', ['posts:read', 'posts:write']);

        return response()->json([
            'token'      => $token->plainTextToken,
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
```

### Token abilities

```php
// Assign abilities on creation
$token = $user->createToken('dashboard', ['posts:*', 'comments:read']);

// Check in gate / middleware
$request->user()->tokenCan('posts:write');

// Middleware
Route::middleware(['auth:sanctum', 'abilities:posts:write'])->group(...);
Route::middleware(['auth:sanctum', 'ability:posts:read,posts:write'])->group(...);
```

---

## Passport (OAuth2 Server)

Use Passport when you are an OAuth provider (issuing tokens to third-party apps).

```bash
composer require laravel/passport
php artisan passport:install --uuids
php artisan migrate
```

```php
use Laravel\Passport\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

```php
// AppServiceProvider::boot()
Passport::tokensExpireIn(now()->addDays(15));
Passport::refreshTokensExpireIn(now()->addDays(30));
Passport::personalAccessTokensExpireIn(now()->addMonths(6));
Passport::hashClientSecrets();

// Routes
Passport::routes();
```

```php
// config/auth.php
'guards' => [
    'api' => [
        'driver'   => 'passport',
        'provider' => 'users',
    ],
],
```

---

## WebAuthn / Passkeys (L13)

Laravel 13 starter kits ship passkey auth out of the box via Fortify.

```bash
# New apps — included in starter kit
# Existing apps
composer require laravel/fortify
php artisan fortify:install
php artisan migrate
```

```php
// FortifyServiceProvider or AppServiceProvider
use Laravel\Fortify\Fortify;

Fortify::authenticateUsing(function (Request $request): ?User {
    // Custom logic, falls back to passkey if configured
});

// In config/fortify.php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication(['confirm' => true]),
    Features::passkeys(),  // L13
],
```

Passkey flow: user registers a biometric credential → private key stays on device → authentication is phishing-proof by design.

---

## Authorization — Policies & Gates

```bash
php artisan make:policy PostPolicy --model=Post
```

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

final class PostPolicy
{
    // Called before all other methods
    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true; // admins can do anything
        }
        return null; // defer to individual methods
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return $post->status === PostStatus::Published
            || $user->id === $post->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function publish(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            && $post->status === PostStatus::Draft;
    }
}
```

Register in `AppServiceProvider` or rely on auto-discovery (models in `App\Models`):

```php
Gate::policy(Post::class, PostPolicy::class);
```

Usage:

```php
// In controller
$this->authorize('update', $post);

// In service
Gate::authorize('publish', $post);

// In resource
'can' => [
    'update' => $request->user()?->can('update', $this->resource),
    'delete' => $request->user()?->can('delete', $this->resource),
],

// Blade
@can('update', $post)
    <button>Edit</button>
@endcan
```

### PHP Attribute authorization (L13)

```php
use Illuminate\Auth\Access\Attributes\Authorize;

#[Authorize(PostPolicy::class, 'update')]
public function update(UpdatePostRequest $request, Post $post): PostResource
{
    // ...
}
```

---

## PreventRequestForgery (L13)

Laravel 13 formalizes CSRF middleware as `PreventRequestForgery` with origin-aware verification.

```php
// bootstrap/app.php — default config
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'api/*',          // API routes use token auth, not cookies
        'webhooks/*',
    ]);
})
```

The new origin check validates that the `Origin` header matches the app URL on state-mutating requests. This is additional protection on top of the CSRF token. If you have cross-origin subdomain setups, review your `SESSION_DOMAIN` and `CORS_ALLOWED_ORIGINS` config after upgrading.
