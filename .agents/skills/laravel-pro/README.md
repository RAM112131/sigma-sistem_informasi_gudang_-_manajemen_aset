# laravel-pro

[![Laravel 13](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php)](https://php.net)

A complete, production-grade agent skill for Laravel 13 development. Covers the full development lifecycle — Eloquent ORM, migrations, controllers, service layer, API resources, queues, jobs, events, auth (Sanctum/Passport/passkeys), Pest testing, and Laravel Boost / AI SDK integration. Built for PHP 8.3+ with all Laravel 13 conventions.

## Coverage

| Area | What's included |
|------|----------------|
| **Eloquent** | Models, all relationship types, scopes, casts, observers, PHP attributes (L13) |
| **Migrations** | Column types, indexes, foreign keys, pivot tables, factories, seeders |
| **HTTP** | Controllers, form requests, middleware, route model binding, versioning |
| **Services** | Service classes, actions, repositories, DTOs, transactions, DI container |
| **API Resources** | JSON resources, JSON:API (L13 first-party), collection resources, error handling |
| **Queues** | Jobs, batching, chaining, Horizon, `Queue::route()` class routing (L13) |
| **Events** | Events, listeners, Reverb broadcasting with database driver (L13) |
| **Notifications** | Mail, Slack, database notifications, on-demand routing |
| **Auth** | Sanctum, Passport, WebAuthn/passkeys (L13), policies, gates |
| **Testing** | Pest, feature tests, all fakes (Queue/Mail/Event/HTTP), datasets, custom expectations |
| **Laravel 13** | Full changelog, breaking changes, upgrade checklist |
| **Boost / AI SDK** | MCP tools, AI guidelines, Laravel AI SDK, vector search |

## Files

```
laravel-pro/
├── SKILL.md                    ← triggers automatically on Laravel tasks
└── references/
    ├── eloquent.md
    ├── migrations.md
    ├── routing.md
    ├── services.md
    ├── api.md
    ├── queues.md
    ├── events.md
    ├── notifications.md
    ├── auth.md
    ├── testing.md
    ├── laravel13.md
    └── boost-ai.md
```

`SKILL.md` stays lean — the agent loads individual reference files only when needed for the task at hand.

## Install

```bash
# Single skill via npx
npx skills add ikamal7/agent-skills --skill laravel-pro

# Via Laravel Boost
php artisan boost:add-skill ikamal7/agent-skills --skill laravel-pro

# Manual
cp -r skills/laravel-pro .claude/skills/
```

## Changelog

### v2.0.0 — April 2026

Initial release targeting Laravel 13 (PHP 8.3+). Covers all L13-specific additions: PHP attribute syntax, JSON:API resources, `Queue::route()`, `Cache::touch()`, passkeys/WebAuthn, Reverb database driver, `PreventRequestForgery`, Laravel AI SDK, Laravel Boost MCP, and vector/semantic search.
