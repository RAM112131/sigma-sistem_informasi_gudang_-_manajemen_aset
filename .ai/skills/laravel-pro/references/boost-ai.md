# Laravel Boost & AI SDK Reference — Laravel 13

## Table of Contents
1. [Laravel Boost Overview](#laravel-boost-overview)
2. [Installation](#installation)
3. [MCP Tools Available](#mcp-tools-available)
4. [AI Guidelines & Skills](#ai-guidelines--skills)
5. [Boost in Claude Code / Cursor](#boost-in-claude-code--cursor)
6. [Laravel MCP Server](#laravel-mcp-server)
7. [Laravel AI SDK](#laravel-ai-sdk)

---

## Laravel Boost Overview

Laravel Boost is the official Laravel MCP (Model Context Protocol) server for AI-assisted development.

It gives AI coding agents (Claude Code, Cursor, etc.) **three capabilities**:
1. **MCP tools** — inspect your app's routes, schema, logs, run queries, execute Tinker code
2. **AI guidelines** — composable, version-aware instructions for Laravel conventions
3. **Documentation API** — 17,000+ pieces of Laravel knowledge with semantic search

Install it as a dev dependency. It has zero production impact.

Works with: Laravel 10, 11, 12, 13 (PHP 8.1+)

---

## Installation

```bash
# Install as dev dependency
composer require laravel/boost --dev

# Run installer — auto-detects IDE and AI agent
php artisan boost:install

# Generated files (add to .gitignore if each dev configures their own):
# .mcp.json      → MCP server config for editors
# CLAUDE.md      → AI guidelines loaded by Claude Code
# boost.json     → Boost configuration
# .ai/guidelines → Composable guideline files

# Keep guidelines updated as packages change
php artisan boost:update

# Discover newly installed packages and offer their guidelines
php artisan boost:update --discover
```

---

## MCP Tools Available

Boost exposes 15+ tools to your AI agent:

| Tool | What it does |
|------|-------------|
| `application-info` | PHP/Laravel versions, DB engine, installed packages, Eloquent models |
| `db-schema` | Full database schema with intelligent analysis |
| `db-query` | Execute read queries directly from the AI |
| `list-routes` | Inspect and analyze all application routes |
| `list-artisan-commands` | Browse available Artisan commands |
| `read-log-entries` | Read Laravel log file entries |
| `last-error` | Fetch the most recent application error |
| `browser-logs` | Access browser console logs from Vite/Mix frontends |
| `tinker` | Execute arbitrary PHP in your app context (like `php artisan tinker`) |
| `search-docs` | Query the Laravel documentation API — version-aware, semantic search |
| `report-feedback` | Send feedback to the Boost team |

### Useful Boost workflows

```
# Diagnose N+1 — give these to your AI agent together
"Use list-routes to find the /posts endpoint, then db-query to run
EXPLAIN on the posts query, and search-docs for Eloquent eager loading best practices."

# Debug an error
"Use last-error to get the most recent exception, then read-log-entries
for context, then tinker to test the fix."

# Generate a feature
"Use db-schema to understand the users and posts tables, application-info
to confirm packages, then build a PostService with full CRUD."
```

---

## AI Guidelines & Skills

Boost ships composable guideline files for Laravel and 16+ ecosystem packages including:
- Laravel (core conventions, routing, Eloquent, queues)
- Livewire 3
- Inertia v3 (React / Vue)
- Sanctum
- Horizon
- Reverb
- Pest
- Filament
- ...and more

These are loaded into your coding agent's context automatically when relevant.

### Custom Guidelines

Add `.blade.php` files to `.ai/guidelines/` to extend or override defaults:

```
.ai/
  guidelines/
    project-conventions.blade.php   # your team's patterns
    inertia-react/
      2/
        forms.blade.php             # override default Inertia form guideline
```

```blade
{{-- .ai/guidelines/project-conventions.blade.php --}}
# Project Conventions

## Naming
- Services go in `app/Services/`, suffixed with `Service`
- DTOs go in `app/Data/`, suffixed with `Data`
- Always use `final` on service and controller classes

## Response format
- API responses always use API Resources
- Paginated responses use `->paginate(15)` by default
```

---

## Boost in Claude Code / Cursor

After `boost:install`, Boost auto-configures your editor's MCP server.

### Manual registration if needed

```json
// .mcp.json (for Claude Code / Cursor / OpenCode)
{
  "mcpServers": {
    "laravel-boost": {
      "command": "php",
      "args": ["artisan", "mcp:start", "boost"]
    }
  }
}
```

### Slash commands (Claude Code)

```
/upgrade-laravel-v13    # guided L12 → L13 upgrade (requires Boost ^2.0)
```

### Typical Claude Code session

```
You: Create a PostService with paginate, create, update, publish, delete methods.
     Use db-schema to understand the posts table first.

Claude Code: [calls db-schema MCP tool]
             [calls search-docs for PostService patterns]
             [generates service following Boost guidelines]
```

---

## Laravel MCP Server

For building your **own** MCP server inside a Laravel app (to expose your app to AI clients like ChatGPT, Cursor, Claude).

Different from Boost: Boost is for development tooling. Laravel MCP is for exposing your production app.

```bash
composer require laravel/mcp
php artisan vendor:publish --provider="Laravel\Mcp\MpcServiceProvider"
php artisan make:mcp-server WeatherServer
php artisan make:mcp-tool CurrentWeatherTool
```

```php
// routes/ai.php
use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Facades\Mcp;

// HTTP server — for remote AI clients
Mcp::http('weather', WeatherServer::class);

// Local server — for Claude Code / Cursor (like Boost does internally)
Mcp::local('weather', WeatherServer::class);
```

```php
// Tool example
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Returns the current weather for a city.')]
final class CurrentWeatherTool extends Tool
{
    public function handle(Request $request, Response $response): Response
    {
        $city = $request->input('city');
        $weather = WeatherService::for($city)->current();

        return $response->text("Current weather in {$city}: {$weather->summary}");
    }
}
```

Authentication: Passport (OAuth 2.1, recommended) or Sanctum.

---

## Laravel AI SDK

Full reference in `references/laravel13.md` → [Laravel AI SDK](#laravel-ai-sdk).

Quick patterns for common tasks:

```php
// Summarize content
$summary = (string) AI::text("Summarize in 2 sentences: {$post->body}");

// Classify
$category = (string) AI::text(
    "Classify this post into one of [tech, lifestyle, business]: {$post->title}"
);

// Generate embeddings for semantic search
$embedding = AI::embedding($post->body);
$post->update(['embedding' => $embedding]);

// Agent with tools
$agent = MyAgent::make()
    ->withTools([SearchDocsTool::class, CreateIssueTool::class])
    ->prompt('Find the latest Laravel docs on Eloquent and create a GitHub issue summarizing gaps');

// Queue AI work (don't block HTTP requests)
AI::text("Generate SEO metadata for: {$post->title}")
    ->queue(
        then: fn ($result) => $post->update(['seo_meta' => $result]),
    );
```
