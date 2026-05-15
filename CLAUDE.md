# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install          # Install dependencies (flight, medoo, gregwar/captcha)
frankenphp run            # Start dev server using Caddyfile (Worker mode)
frankenphp run --config Caddyfile.dev  # Start with alternate config (Classic mode for dev)
```

No test suite or linter is configured in this project.

## Project Skills

This project includes 5 custom skills in `.claude/skills/`. When a task matches a skill's domain, invoke it via the Skill tool for detailed guidance:

| Skill | File | When to Use |
|-------|------|-------------|
| **crud-admin** | `.claude/skills/crud-admin/SKILL.md` | Creating standard CRUD admin modules (configuration-driven, zero frontend code) |
| **view-admin** | `.claude/skills/view-admin/SKILL.md` | Creating fully custom admin pages with PHP/HTML/CSS/JS (View mode, iframe) |
| **frontend-view** | `.claude/skills/frontend-view/SKILL.md` | Creating public-facing frontend pages — list pages, detail pages, landing pages, error pages |
| **api-development** | `.claude/skills/api-development/SKILL.md` | Creating any non-CRUD API endpoint — client APIs (`/api/*`) or admin APIs (`/api/admin/*`) |
| **database** | `.claude/skills/database/SKILL.md` | Database table design, CrudConfig-to-SQL type mapping, CREATE/ALTER via MCP |
| **medoo-query** | `.claude/skills/medoo-query/SKILL.md` | Medoo ORM query syntax reference — JOINs, WHERE modifiers, aggregates, common mistakes |

Each skill contains the full workflow, code templates, and conventions for its domain. Load the relevant skill before starting implementation.

## MCP MySQL Tools

A MySQL MCP server is configured at user level. The following tools are available for database operations — prefer them over raw `mysql` CLI commands:

- `mcp__mysql__connect_db` — Connect with credentials from `.env` (always first step)
- `mcp__mysql__list_tables` — List all tables
- `mcp__mysql__describe_table` — Show table structure
- `mcp__mysql__query` — Execute SELECT queries (read-only)
- `mcp__mysql__execute` — Execute INSERT/UPDATE/DELETE/DDL

## Architecture

This is a **configuration-driven admin panel framework** built on Flight PHP micro-framework (v3), Medoo ORM (v2), and Layui 2.8+ frontend.

### Three server modes, one shared bootstrap

```
public/index.php  ──┐
                    ├──→  app/bootstrap.php  (init, DB, middleware, all routes)
public/worker.php ──┘
```

- **index.php** — PHP-FPM / FrankenPHP Classic. Full lifecycle per request.
- **worker.php** — FrankenPHP Worker mode. `bootstrap.php` runs once, requests loop via `frankenphp_handle_request()`. Sets `APP_WORKER_MODE` constant before requiring bootstrap.
- **bootstrap.php** — shared by both. Contains autoload require, `.env` loading, error reporting, Medoo DB registration, CORS + request logging middleware, and **all route definitions**.

### Configuration-driven CRUD vs View mode

The framework has two modes that coexist in the same menu system:

| | CRUD mode | View mode |
|---|---|---|
| **How** | PHP config array (`CrudConfig.php`) → `crud-renderer.js` auto-generates table + form | Custom PHP page loaded via iframe |
| **Auth** | AJAX Bearer token header | Cookie `admin_token` (read by PHP) |
| **Entry** | `ConfigController::getPageConfig()` | `AdminViewController::render($viewName)` |
| **View file** | None (renderer builds DOM) | `app/views/admin/{viewName}.php` |

### Adding a new CRUD module (4 steps)

1. **Create controller** `app/api/admin/XxxController.php` — static methods: `list()`, `create()`, `update($id)`, `delete($id)`. Use `AuthMiddleware::checkAdmin()` + `checkPermission('module', 'action')` at the top of each method.
2. **Add config** in `app/config/CrudConfig.php` — a static method returning `page`, `table`, `form`, `api`, `actions`, `search`, `toolbar` arrays.
3. **Register routes** in `app/bootstrap.php` — standard pattern:
   ```php
   Flight::route('GET /api/admin/modules', fn() => \App\api\admin\XxxController::list());
   Flight::route('POST /api/admin/module', fn() => \App\api\admin\XxxController::create());
   Flight::route('POST /api/admin/module/@id', fn($id) => \App\api\admin\XxxController::update($id));
   Flight::route('DELETE /api/admin/module/@id', fn($id) => \App\api\admin\XxxController::delete($id));
   ```
4. **Add menu entry** in `CrudConfig::getMenus()`.

### Key helper functions (autoloaded via composer `files`)

- **Response**: `success($data, $msg)`, `error($msg, $code)`, `layuiTable($data, $count)` — all call `terminateRequest()` after outputting JSON.
- **DB**: `db()` returns the Medoo instance (registered as `Flight::db()`).
- **Request**: `getPost($key)`, `getQuery($key)`, `getHeader($key)`.
- **Auth**: `currentUser()`, `generateToken($user)`, `validateToken($token)` — token is base64-encoded JSON with id/username/type/exp.
- **Permissions**: `hasPermission($adminId, $module, $action)`, `checkPermission($module, $action)` — JSON-based, admin ID=1 is super admin.
- **Security**: `cleanInput()`, `hashPassword()`/`verifyPassword()`, `checkRateLimit($key, $max, $window)`.
- **Logging**: `writeLog($msg, $level)` — writes to `runtime/logs/YYYY-MM-DD.log`.

### Permission system

- `og_admin` table has a `permissions` column (JSON): `{"users": ["list","create","update","delete"], "articles": ["list","export"]}`.
- Admin ID=1 is super admin — `hasPermission()` returns `true` unconditionally.
- `checkPermission($module, $action)` reads `admin_id` from `Flight::get('admin_id')`, set by `AuthMiddleware::checkAdmin()`.
- View-mode menu items use permission key `view_{viewName}` with action `access`.

### Worker mode critical rules

- **Never use `exit`/`die`** — it kills the worker process. Use `terminateRequest()` instead (throws `RequestTerminatedException` in worker mode, calls `exit` in FPM/Classic).
- `success()`, `error()`, `layuiTable()` already use `terminateRequest()` — no manual handling needed.
- After `terminateRequest()`, code below it **does not execute** — same as `exit`.
- `worker.php` clears `Flight::set('currentUser')`, `userType`, `admin_id` before each request to prevent state leakage.
- DB connection is long-lived (PDO reuse). MySQL `wait_timeout` may disconnect idle workers.
- `static` variable caches in your code persist across requests. Framework's `config()` uses `static` intentionally (config is static). Don't `static`-cache DB query results without a TTL.

## Project conventions

- Autoload: PSR-4 `App\` → `app/`, with helper files loaded via composer `files` array.
- Web root is `public/` — all HTTP entry points and static assets live there.
- Database table prefix: `og_` (configurable via `DB_PREFIX` in `.env`).
- Config: `.env` at project root loaded by `app/helpers/env.php`.
- Controllers use **static methods**, not instance methods. The Flight router calls them directly.
- No built-in DI container — use `Flight::register()`, `Flight::db()`, `Flight::set()`/`Flight::get()`.
