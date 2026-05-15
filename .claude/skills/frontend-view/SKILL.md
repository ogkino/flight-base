---
name: frontend-view
description: Create frontend public pages for Flight Base — standalone PHP-rendered HTML pages (list pages, detail pages, landing pages, error pages). For client-facing pages, not admin panel pages.
---

# Frontend View Development Skill

Use this skill when creating **public-facing frontend pages** — the pages visitors see, rendered server-side via `Flight::render()`. Examples: landing pages, article lists, article details, search results, error pages.

For admin panel custom pages, use the `view-admin` skill instead. For CRUD modules, use `crud-admin`.

## How It Works

```
User visits URL
    ↓
Route in bootstrap.php matches
    ↓
Controller method runs
    1. Queries data from DB via db()
    2. Passes data to Flight::render('path/to/view', $data)
    ↓
View file (full HTML page) renders with $data variables
    ↓
HTML sent to browser
```

## 3-Step Process

```
1. Create view file (app/views/{module}/{page}.php)
2. Create controller method (app/api/XxxController.php)
3. Register route (app/bootstrap.php)
```

---

## Step 1: Create View File

File: `app/views/{module}/{page}.php`

Frontend views are **standalone full HTML pages** — no shared layout files (unlike admin views which use `_head.php` / `_foot.php`). Each view contains its own `<html>`, `<head>`, `<style>`, `<body>`, and inline CSS.

### Template: Simple Static Page

```php
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? '页面标题'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($title ?? '默认标题'); ?></h1>
        <!-- Your content here -->
    </div>
</body>
</html>
```

### Template: List Page

```php
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? '列表'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #1E90FF; padding-bottom: 10px; }
        .item-list { list-style: none; }
        .item {
            display: flex; gap: 20px;
            border-bottom: 1px solid #eee; padding: 20px 0;
            transition: all 0.3s;
        }
        .item:hover { background: #f9f9f9; padding: 20px; margin: 0 -20px; border-radius: 4px; }
        .item-info { flex: 1; }
        .item-title { font-size: 20px; margin-bottom: 10px; font-weight: 600; }
        .item-title a { color: #333; text-decoration: none; }
        .item-title a:hover { color: #1E90FF; }
        .item-meta { color: #999; font-size: 13px; margin-bottom: 10px; }
        .item-meta span { margin-right: 15px; }
        .item-excerpt { color: #666; line-height: 1.8; font-size: 14px; }
        .empty { text-align: center; padding: 60px 20px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($title ?? '列表'); ?></h1>

        <?php if (empty($items)): ?>
            <div class="empty">
                <div class="empty-icon">📄</div>
                <p style="font-size: 16px;">暂无内容</p>
            </div>
        <?php else: ?>
            <ul class="item-list">
                <?php foreach ($items as $item): ?>
                    <li class="item">
                        <div class="item-info">
                            <div class="item-title">
                                <a href="/item/<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </div>
                            <div class="item-meta">
                                <span>📅 <?php echo date('Y-m-d', strtotime($item['created_at'])); ?></span>
                            </div>
                            <div class="item-excerpt">
                                <?php echo htmlspecialchars(mb_substr(strip_tags($item['content'] ?? ''), 0, 120)); ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
```

### Template: Detail Page

```php
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title'] ?? '详情'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .back-btn { display: inline-block; color: #1E90FF; text-decoration: none; margin-bottom: 30px; font-size: 14px; }
        .back-btn:hover { text-decoration: underline; }
        h1 { color: #333; margin-bottom: 20px; line-height: 1.4; font-size: 32px; font-weight: 700; }
        .meta { color: #999; font-size: 14px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0; margin-bottom: 30px; }
        .meta span { margin-right: 20px; }
        .content { color: #333; line-height: 1.8; font-size: 16px; }
        .content img { max-width: 100%; height: auto; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/items" class="back-btn">← 返回列表</a>

        <h1><?php echo htmlspecialchars($item['title']); ?></h1>

        <div class="meta">
            <span>📅 <?php echo date('Y-m-d', strtotime($item['created_at'])); ?></span>
        </div>

        <div class="content">
            <?php echo $item['content']; ?>
        </div>
    </div>
</body>
</html>
```

### Data in Views

All array keys passed to `Flight::render()` are extracted to variables:

```php
// Controller:
Flight::render('module/page', [
    'title' => 'Hello',
    'items' => $items,
    'item'  => $item,
]);

// View: $title, $items, $item are available
echo htmlspecialchars($title);
foreach ($items as $item) { ... }
```

**Always use `htmlspecialchars()`** when outputting user-generated content. For rich HTML content (stored from WYSIWYG editors), output directly but ensure it was sanitized on save.

---

## Step 2: Create Controller Method

File: `app/api/XxxController.php` (or add to existing controller)

Controller methods for frontend views:
1. Are `public static` methods
2. Query data using `db()`
3. Call `Flight::render('path/to/view', $data)`
4. Handle 404 by rendering error view then `return`

### Pattern: List Page Controller

```php
<?php
namespace App\api;

use Flight;

class XxxController
{
    public static function listPage()
    {
        $items = db()->select('table_name', '*', [
            'status' => 1,
            'ORDER' => ['id' => 'DESC'],
            'LIMIT' => 20
        ]);

        Flight::render('module/list', [
            'title' => '列表标题',
            'items' => $items
        ]);
    }
}
```

### Pattern: Detail Page Controller

```php
public static function detailPage($id)
{
    $item = db()->get('table_name', '*', [
        'id' => $id,
        'status' => 1
    ]);

    if (!$item) {
        Flight::render('errors/404', ['message' => '内容不存在']);
        return;
    }

    Flight::render('module/detail', [
        'item' => $item
    ]);
}
```

### Pattern: Multi-Table Join (with author name from users table)

```php
public static function listPage()
{
    $items = db()->select('articles', [
        '[>]users' => ['author' => 'id']
    ], [
        'articles.id',
        'articles.title',
        'articles.content',
        'articles.created_at',
        'users.nickname(author)'
    ], [
        'articles.status' => 1,
        'ORDER' => ['articles.id' => 'DESC'],
        'LIMIT' => 20
    ]);

    Flight::render('articles/list', [
        'title' => '文章列表',
        'articles' => $items
    ]);
}
```

---

## Step 3: Register Route

File: `app/bootstrap.php`

Add route in the **前端页面路由** section (around line 109):

```php
// List page
Flight::route('GET /items', function () {
    \App\api\XxxController::listPage();
});

// Detail page (with @id parameter)
Flight::route('GET /item/@id', function ($id) {
    \App\api\XxxController::detailPage($id);
});

// Simple static page
Flight::route('GET /about', function () {
    Flight::render('home/about');
});
```

Route conventions:
- **List page**: `GET /{plural}` → `listPage()`
- **Detail page**: `GET /{singular}/@id` → `detailPage($id)`
- **Static page**: `GET /{path}` → `Flight::render()` directly

---

## Error Pages

The project has a shared error view at `app/views/errors/404.php` that accepts a `$message` variable. Use it from any controller:

```php
if (!$item) {
    Flight::render('errors/404', ['message' => '文章不存在']);
    return;  // important: return after render to stop further execution
}
```

To add other error pages, create files like `app/views/errors/500.php` and register routes:
```php
Flight::route('GET /error', function () {
    Flight::render('errors/500', ['message' => '服务器错误']);
});
```

---

## Frontend View Conventions

- **Naming**: `app/views/{module}/{action}.php` — module is usually plural for lists, singular for details (e.g., `articles/list.php`, `articles/detail.php`)
- **No shared layouts**: Each view is a complete HTML document. There is no shared header/footer (unlike admin views which use `_head.php` / `_foot.php`).
- **Inline CSS**: Styles are in `<style>` blocks within each view. No external CSS framework on the frontend.
- **No JavaScript framework**: Frontend pages are server-rendered. Any JS needed is inline. jQuery/Layui are NOT loaded on frontend pages.
- **Always escape output**: Use `htmlspecialchars()` for all user-generated text. Rich HTML content from WYSIWYG editors is output directly (sanitized on save).
- **Data passed via `Flight::render()`**: Keys become variables in the view scope.
- **`Flight::render()` does not return** — code after it still executes. Use `return;` after rendering a 404 to prevent further logic.
- **No `exit`/`die`**: Use `return` after `Flight::render()` for control flow. `terminateRequest()` is available if needed but rarely used in views.

## Common Pitfalls

- **Don't call `exit`/`die`** — in Worker mode it kills the process. Use `return` after `Flight::render()` for control flow.
- **`Flight::render()` does not return a response** — it outputs directly. The code below it still runs (unlike `success()`/`error()` which call `terminateRequest()`).
- **No Layui/jQuery on frontend** — those are admin-only. Frontend pages should be simple HTML+CSS (or add your own JS if needed).
- **Use htmlspecialchars()** — all user-originated data in HTML context must be escaped.
- **404 handling**: Render error view THEN `return;` — do NOT call `terminateRequest()` or `exit`.
- **Not an iframe**: Unlike admin View mode, frontend pages load directly in the browser tab.
