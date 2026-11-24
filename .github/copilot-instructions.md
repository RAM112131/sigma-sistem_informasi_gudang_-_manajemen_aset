# context-mode — MANDATORY routing rules

context-mode MCP tools available. Rules protect context window from flooding. One unrouted command dumps 56 KB into context.

## Think in Code — MANDATORY

Analyze/count/filter/compare/search/parse/transform data: **write code** via `ctx_execute(language, code)`, `console.log()` only the answer. Do NOT read raw data into context. PROGRAM the analysis, not COMPUTE it. Pure JavaScript — Node.js built-ins only (`fs`, `path`, `child_process`). `try/catch`, handle `null`/`undefined`. One script replaces ten tool calls.

## BLOCKED — do NOT attempt

### curl / wget — BLOCKED
Terminal `curl`/`wget` intercepted and blocked. Do NOT retry.
Use: `ctx_fetch_and_index(url, source)` or `ctx_execute(language: "javascript", code: "const r = await fetch(...)")`

### Inline HTTP — BLOCKED
`fetch('http`, `requests.get(`, `requests.post(`, `http.get(`, `http.request(` — intercepted. Do NOT retry.
Use: `ctx_execute(language, code)` — only stdout enters context

### WebFetch / fetch — BLOCKED
Use: `ctx_fetch_and_index(url, source)` then `ctx_search(queries)`

## REDIRECTED — use sandbox

### Terminal / run_in_terminal (>20 lines output)
Terminal ONLY for: `git`, `mkdir`, `rm`, `mv`, `cd`, `ls`, `npm install`, `pip install`.
Otherwise: `ctx_batch_execute(commands, queries)` or `ctx_execute(language: "javascript", code: "...")`. Use `language: "shell"` only when code matches the host shell.

### read_file (for analysis)
Reading to **edit** → read_file correct. Reading to **analyze/explore/summarize** → `ctx_execute_file(path, language, code)`.

### grep / search (large results)
Use `ctx_execute(language: "javascript", code: "...")` in sandbox for portable filtering/counting.

## Tool selection

0. **MEMORY**: `ctx_search(sort: "timeline")` — after resume, check prior context before asking user.
1. **GATHER**: `ctx_batch_execute(commands, queries)` — runs all commands, auto-indexes, returns search. ONE call replaces 30+. Each command: `{label: "header", command: "..."}`.
2. **FOLLOW-UP**: `ctx_search(queries: ["q1", "q2", ...])` — all questions as array, ONE call (default relevance mode).
3. **PROCESSING**: `ctx_execute(language, code)` | `ctx_execute_file(path, language, code)` — sandbox, only stdout enters context.
4. **WEB**: `ctx_fetch_and_index(url, source)` then `ctx_search(queries)` — raw HTML never enters context.
5. **INDEX**: `ctx_index(content, source)` — store in FTS5 for later search.

### Parallel I/O batches
Pass `concurrency: 4-8` to `ctx_batch_execute` and `ctx_fetch_and_index` for network/API batches. Keep `concurrency: 1` for CPU-bound work (test, build, lint). GitHub gh: cap at 4.

## Output

Write artifacts to FILES — never inline. Return: file path + 1-line description.
Descriptive source labels for `ctx_search(source: "label")`.

## Session Continuity

Skills, roles, and decisions persist for the entire session. Do not abandon them as the conversation grows.

## Memory

Session history is persistent and searchable. On resume, search BEFORE asking the user:

| Need | Command |
|------|---------|
| What were we working on? | `ctx_search(queries: ["summary"], source: "compaction", sort: "timeline")` |
| What did we decide? | `ctx_search(queries: ["decision"], source: "decision", sort: "timeline")` |
| What NOT to repeat? | `ctx_search(queries: ["rejected"], source: "rejected-approach")` |
| What constraints exist? | `ctx_search(queries: ["constraint"], source: "constraint")` |

Note: user-prompt history not available.

DO NOT ask "what were we working on?" — SEARCH FIRST.
If search returns 0 results, proceed as a fresh session.

## ctx commands

| Command | Action |
|---------|--------|
| `ctx stats` | Call `ctx_stats` MCP tool, display full output verbatim |
| `ctx doctor` | Call `ctx_doctor` MCP tool, run returned shell command, display as checklist |
| `ctx upgrade` | Call `ctx_upgrade` MCP tool, run returned shell command, display as checklist |
| `ctx purge` | Call `ctx_purge` MCP tool with confirm: true. Warns before wiping knowledge base. |

After /clear or /compact: knowledge base and session stats preserved. Use `ctx purge` to start fresh.

## Repository guide

### Build, tests, and formatting

- `composer test` clears Laravel's config cache, then runs the PHPUnit suite; `php artisan test` runs the suite directly.
- Run one PHP test file with `php artisan test tests/Feature/StoreStockImportTest.php`, or select a test with `php artisan test --filter=test_valid_receipt_persists_header_and_item`.
- Tests use PostgreSQL and `phpunit.xml` targets the isolated `db_wms_test` database. Ensure that database is available; do not point test runs at the application database.
- `vendor/bin/pint` formats PHP. `npm run format:blade` formats Blade templates.
- `npm run build` runs the Vite production build; `npm run dev` starts the Vite development server.
- The JavaScript test suite uses Node's built-in runner: `node --test tests/js/recording-gu-receipt-service.test.js`.

### Architecture

- SIGMA is a warehouse and inventory application. `routes/web.php` groups authenticated pages by area (`admin`, `store`, `unit-bisnis`, and `master`); `bootstrap/app.php` registers web middleware, account-status checks, and Spatie role/permission aliases.
- The UI is primarily server-rendered Blade. Controllers are organized by area under `app/Http/Controllers`, with related business workflows in matching `app/Services` domains. Models and migrations describe warehouses, catalog items, and transaction documents; receipt/issue workflows commonly persist a document header and detail rows together.
- Stock access is scoped to a `Gudang` as well as protected by roles or permissions. Preserve both checks when changing inventory, reports, or warehouse selection.
- Excel imports/exports live in `app/Imports` and `app/Exports`; PDF output uses the existing export/report flows. Blade pages are organized under `resources/views`. Vite bundles `resources/css/app.css` and `resources/js/app.js`; frontend behavior uses Alpine.js and Turbo, with feature modules in `resources/js`.
- Semantic search uses `EmbeddingGenerator`, queued `GenerateVectorEmbedding` jobs, and `SemanticVectorSearch`. PostgreSQL `pgvector` is optional and text search is the fallback. The configured vector size is 1536; changing it requires a compatible provider/model and database migration. Read the semantic-search section of `README.md` before changing indexing or provider behavior.

### Codebase conventions

- Keep changes within the existing domain structure and follow the nearest controller/service/view/test example. Controllers handle HTTP input and responses; multi-record business workflows belong in their domain service. Use transactions when a workflow writes related inventory records, and dispatch dependent work after commit where applicable.
- Route names use area prefixes such as `store.` and `unit-bisnis.`; prefer these named routes in views and redirects. Authorization is expressed through the route middleware and permission checks used by each area.
- User-facing labels, validation messages, and workflows are predominantly in Indonesian. Preserve the existing terminology and localization in the affected area.
- Tests are PHPUnit tests under `tests/Feature` and `tests/Unit`; use the established fixtures and role/permission setup for the area being tested. The JavaScript tests are separate and run with Node's built-in test runner.
- The root `AGENTS.md` contains shared Laravel project rules. Check package versions in `composer.json` and `package.json`, and use relevant skills under `.agents/skills` when working in a covered domain.
