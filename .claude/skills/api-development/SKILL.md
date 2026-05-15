---
name: api-development
description: Create API endpoints for Flight Base framework — covers both client-facing APIs (/api/*) and admin APIs (/api/admin/*). Use for any non-CRUD endpoint: auth, data queries, file uploads, third-party callbacks, health checks, etc.
---

# API Development Skill

Use this skill when creating **API endpoints** that are NOT part of the configuration-driven CRUD admin system. This covers **both**:

| API Type | Route Prefix | Namespace | Directory | Auth Middleware | User Table |
|----------|-------------|-----------|-----------|-----------------|------------|
| **客户端 API** | `/api/*` | `App\api` | `app/api/` | `AuthMiddleware::check()` | `og_users` |
| **后台 API** | `/api/admin/*` | `App\api\admin` | `app/api/admin/` | `AuthMiddleware::checkAdmin()` | `og_admin` |

**Do NOT use this skill for:**
- Standard CRUD admin modules → use `crud-admin` skill
- Custom admin HTML pages → use `view-admin` skill

---

## Quick Decision

```
Is this API for the admin panel (management backend)?
  ├─ Yes → Admin API: namespace App\api\admin, file in app/api/admin/, checkAdmin()
  └─ No  → Client API: namespace App\api, file in app/api/, check() if login needed
```

---

## Client API Pattern

For public-facing APIs consumed by the frontend website/app.

**File:** `app/api/XxxController.php` | **Namespace:** `App\api` | **Route prefix:** `/api/`

### Public endpoint (no login required):
```php
<?php
namespace App\api;

class ArticleController
{
    // Return JSON
    public static function listApi()
    {
        $db = db();
        $page  = max(1, (int)getQuery('page', 1));
        $limit = max(1, (int)getQuery('limit', 10));

        $count = $db->count('articles', ['is_published' => 1]);
        $rows  = $db->select('articles', '*', [
            'is_published' => 1,
            'ORDER' => ['created_at' => 'DESC'],
            'LIMIT' => [($page - 1) * $limit, $limit]
        ]);

        success(['list' => $rows, 'total' => $count, 'page' => $page, 'limit' => $limit]);
    }

    // Render HTML page (not JSON)
    public static function listPage()
    {
        $articles = db()->select('articles', '*', ['is_published' => 1]);
        Flight::render('articles/list', ['title' => '文章列表', 'articles' => $articles]);
    }
}
```

**Routes:**
```php
Flight::route('GET /articles', function () {           // HTML page
    \App\api\ArticleController::listPage();
});
Flight::route('GET /api/articles', function () {       // JSON API
    \App\api\ArticleController::listApi();
});
```

### Endpoint requiring user login:
```php
<?php
namespace App\api;

use App\middleware\AuthMiddleware;

class OrderController
{
    public static function myOrders()
    {
        AuthMiddleware::check();   // ← client user auth (og_users table)
        $user = currentUser();
        $orders = db()->select('orders', '*', ['user_id' => $user['id']]);
        success($orders);
    }
}
```

---

## Admin API Pattern

For admin panel APIs — the most common type in this framework.

**File:** `app/api/admin/XxxController.php` | **Namespace:** `App\api\admin` | **Route prefix:** `/api/admin/`

```php
<?php
namespace App\api\admin;

use App\middleware\AuthMiddleware;

class XxxController
{
    // With permission check (recommended for admin CRUD endpoints)
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('module_name', 'list');
        // ...
    }

    // Without permission check (auth, dashboard, system endpoints)
    public static function info()
    {
        AuthMiddleware::checkAdmin();
        $admin = currentUser();
        success(['username' => $admin['username'], 'nickname' => $admin['nickname']]);
    }
}
```

**Routes:**
```php
Flight::route('GET /api/admin/my-endpoint', function () {
    \App\api\admin\XxxController::list();
});
Flight::route('POST /api/admin/my-endpoint', function () {
    \App\api\admin\XxxController::action();
});
```

---

## Shared Controller Pattern

Controllers use **static methods** exclusively. One method = one endpoint.

```php
<?php
namespace App\api\admin;  // or App\api for client

use App\middleware\AuthMiddleware;

class XxxController
{
    public static function list()
    {
        AuthMiddleware::checkAdmin();  // or check() for client

        $db      = db();
        $page    = max(1, (int)getQuery('page', 1));
        $limit   = max(1, (int)getQuery('limit', 10));
        $keyword = getQuery('keyword', '');

        $where = [];
        if ($keyword) {
            $where['name[~]'] = $keyword;  // Medoo LIKE
        }

        $count = $db->count('table', $where);
        $data  = $db->select('table', '*', array_merge($where, [
            'LIMIT' => [($page - 1) * $limit, $limit],
            'ORDER' => ['id' => 'DESC']
        ]));

        layuiTable($data, $count);  // For Layui table consumers
        // OR: success($data);      // For non-Layui consumers
    }

    public static function create()
    {
        AuthMiddleware::checkAdmin();
        $db = db();
        $db->insert('table', [
            'name'   => getPost('name'),
            'status' => getPost('status', 1),
        ]);
        success(['id' => $db->id()], '创建成功');
    }

    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        $db = db();
        $db->update('table', [
            'name'   => getPost('name'),
            'status' => getPost('status', 1),
        ], ['id' => $id]);
        success([], '更新成功');
    }

    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        db()->delete('table', ['id' => $id]);
        success([], '删除成功');
    }
}
```

---

## Route Registration

All routes go in `app/bootstrap.php`. Match the existing section structure:

```php
// ========== 公共接口（无需登录）==========
Flight::route('GET /api/health', function () {
    \App\api\HealthController::check();
});

// ========== 前端业务接口 ==========
Flight::route('GET /api/articles', function () {
    \App\api\ArticleController::listApi();
});

// ========== 前端页面路由（Views）==========
Flight::route('GET /articles', function () {
    \App\api\ArticleController::listPage();
});

// ========== 管理后台接口（/api/admin/*）==========
Flight::route('GET /api/admin/my-endpoint', function () {
    \App\api\admin\XxxController::list();
});
```

**URL conventions:**
- `GET /api/xxx` — list/query
- `GET /api/xxx/@id` — get single item
- `POST /api/xxx` — create
- `POST /api/xxx/@id` — update
- `DELETE /api/xxx/@id` — delete

---

## Helper Functions Quick Reference

### Request
```php
$val  = getPost('key', 'default');       // POST body (JSON + form-data)
$val  = getQuery('key', 'default');      // GET query string
$val  = getHeader('Authorization');      // Request header
$all  = getPost();                       // All POST data as array
```

### Response — terminate request, nothing after them executes
```php
success($data, 'message');               // {"code":0, "msg":"message", "data":$data}
error('error message', 400);             // {"code":400, "msg":"error message", "data":null}
layuiTable($rows, $totalCount);          // {"code":0, "count":$totalCount, "data":$rows}
```

### Database (Medoo ORM)
```php
$db = db();
$rows  = $db->select('table', '*', ['status' => 1, 'LIMIT' => 10]);
$row   = $db->get('table', '*', ['id' => $id]);
$count = $db->count('table', ['status' => 1]);
$db->insert('table', ['col' => 'val']);
$newId = $db->id();
$db->update('table', ['col' => 'new'], ['id' => $id]);
$db->delete('table', ['id' => $id]);

// LIKE search
$where['name[~]'] = $keyword;            // WHERE name LIKE '%keyword%'

// Atomic increment
$db->update('articles', ['views[+]' => 1], ['id' => $id]);

// JOIN (Medoo syntax)
$rows = $db->select('articles', [
    '[>]users' => ['author' => 'id']      // LEFT JOIN users ON articles.author = users.id
], [
    'articles.id', 'articles.title',
    'users.nickname(author)'              // Alias: users.nickname AS author
]);

// Transaction (auto rollback on exception)
$db->action(function($db) {
    $db->insert('table1', [...]);
    $db->insert('table2', [...]);
});
```

### Page Rendering (Client API only)
```php
Flight::render('articles/list', [
    'title'    => 'Page Title',
    'articles' => $articles
]);
```

### Auth
```php
AuthMiddleware::check();       // Client user (og_users table)
AuthMiddleware::checkAdmin();  // Admin user (og_admin table)
currentUser();                 // Get current user array from Flight state
checkPermission('module', 'action');  // Admin permission check (after checkAdmin)
```

### Security
```php
cleanInput($data);                        // XSS filter
validateEmail($email);                    // Returns bool
validatePhone($phone);                    // Returns bool (Chinese mobile)
validateUsername($username);              // 4-20 chars, alphanumeric + underscore
hashPassword($pwd) / verifyPassword($pwd, $hash);
checkRateLimit('key_' . $ip, 5, 300);    // 5 attempts per 300 seconds
auditLog('action', ['detail' => 'val']); // Write audit log entry
```

### Utilities
```php
config('app.debug');           // Read app config
writeLog('message', 'info');   // Write to runtime/logs/YYYY-MM-DD.log
randomString(16);              // Generate random alphanumeric string
```

---

## Auth Quick Reference

| Scenario | Middleware | Route Section in bootstrap.php |
|----------|-----------|-------------------------------|
| Public (health, captcha) | None | 公共接口 |
| Client user must be logged in | `AuthMiddleware::check()` | 前端业务接口 |
| Admin must be logged in | `AuthMiddleware::checkAdmin()` | 管理后台接口 |
| Admin + specific permission | `checkPermission('module', 'action')` | 管理后台接口 |

---

## Worker Mode Note

Never use `exit` or `die` — helper functions (`success`, `error`, `layuiTable`) already call `terminateRequest()`. If manual termination is needed:

```php
// ❌ exit;     — kills the worker process in Worker mode
// ✅ terminateRequest();  — throws exception in Worker, calls exit in FPM/Classic
```

---

## Complete Examples

### Example 1: Client API — Product search (public, no login)

**Controller** `app/api/ProductController.php`:
```php
<?php
namespace App\api;

class ProductController
{
    public static function search()
    {
        $keyword = getQuery('keyword', '');
        $where = ['status' => 1];
        if ($keyword) {
            $where['name[~]'] = $keyword;
        }
        $products = db()->select('products', ['id', 'name', 'price', 'cover'],
            array_merge($where, ['LIMIT' => 20]));
        success($products);
    }
}
```

**Route** in bootstrap.php (前端业务接口 section):
```php
Flight::route('GET /api/products/search', function () {
    \App\api\ProductController::search();
});
```

### Example 2: Client API — Render HTML page

**Controller** `app/api/SiteController.php`:
```php
<?php
namespace App\api;

class SiteController
{
    public static function aboutPage()
    {
        Flight::render('home/about', ['site_name' => config('app.name')]);
    }
}
```

**Route** in bootstrap.php (前端页面路由 section):
```php
Flight::route('GET /about', function () {
    \App\api\SiteController::aboutPage();
});
```

### Example 3: Admin API — Dashboard stats (authenticated)

**Controller** `app/api/admin/StatsController.php`:
```php
<?php
namespace App\api\admin;

use App\middleware\AuthMiddleware;

class StatsController
{
    public static function overview()
    {
        AuthMiddleware::checkAdmin();
        $db = db();
        success([
            'total_users'    => $db->count('users'),
            'total_articles' => $db->count('articles'),
            'today_views'    => $db->count('articles', ['updated_at[>=]' => date('Y-m-d')]),
        ]);
    }
}
```

**Route** in bootstrap.php (管理后台接口 section):
```php
Flight::route('GET /api/admin/stats/overview', function () {
    \App\api\admin\StatsController::overview();
});
```
