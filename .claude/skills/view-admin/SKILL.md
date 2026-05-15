---
name: view-admin
description: Create fully custom admin pages (View mode) for Flight Base — PHP-rendered pages with complete UI freedom, loaded via iframe in the admin panel. For dashboards, wizards, complex workflows beyond CRUD config capability.
---

# View Admin Development Skill

Use this skill when creating a **fully custom admin page** that needs complete control over HTML, CSS, and JS — beyond what the CRUD configuration system can express. Examples: complex dashboards with charts, multi-step wizards, custom workflows, data visualization pages.

The View mode loads your PHP page in an iframe inside the admin panel. Auth is handled via Cookie (`admin_token`), and the admin theme (`admin.css`) is automatically applied.

**For standard list/create/edit/delete pages, use the `crud-admin` skill instead.**

## How It Works

```
User clicks menu item (type: 'view')
    ↓
index.html detects data-view attribute → opens iframe
    ↓
GET /admin/view/{viewName}
    ↓
AdminViewController::render()
    1. AuthMiddleware::checkAdmin() — reads admin_token Cookie
    2. Validates viewName (path traversal protection)
    3. Checks view_{name}.access permission (skipped for super admin)
    4. include app/views/admin/{viewName}.php
    5. $currentAdmin variable available in view
    ↓
Your PHP page renders inside iframe
```

## 2-Step Process

Compared to CRUD (4 steps), View mode only needs 2:

```
1. Create view file (PHP template)
2. Add menu entry (in CrudConfig::getMenus())
```

No route registration needed — the route `GET /admin/view/@viewName` already exists in `bootstrap.php`. No controller needed — `AdminViewController` handles everything generically.

---

## Step 1: Create View File

File: `app/views/admin/{viewName}.php`

**Naming rules:** Only `a-zA-Z0-9_-/` allowed. No path traversal. The filename (without `.php`) is the `viewName` used in menu config.

### Base Template

```php
<?php
$pageTitle = '页面标题';
include __DIR__ . '/_head.php';
?>

<div class="view-container">

    <!-- Toolbar -->
    <div class="view-toolbar">
        <div class="view-title">
            <i class="layui-icon layui-icon-template" style="margin-right:6px;"></i>
            页面标题
        </div>
        <div>
            <button class="layui-btn layui-btn-sm layui-btn-normal" onclick="doSave()">
                <i class="layui-icon layui-icon-ok"></i> 保存
            </button>
        </div>
    </div>

    <!-- Card: standard container -->
    <div class="card">
        <div class="card-title">
            <i class="layui-icon layui-icon-tips"></i> 卡片标题
        </div>
        <!-- Your custom HTML here -->
    </div>

</div>

<?php include __DIR__ . '/_foot.php'; ?>

<script>
layui.use(['layer', 'form'], function() {
    window.layer = layui.layer;
});

// Your custom JS
function doSave() {
    request('/api/admin/your-api', {
        method: 'POST',
        data: { key: 'value' }
    }).then(res => {
        if (res.code === 0) layer.msg('保存成功', {icon: 1});
        else layer.msg(res.msg, {icon: 2});
    });
}
</script>

</body>
</html>
```

### What's Available in the View

**PHP variables:**
- `$currentAdmin` — Current admin user array (`id`, `username`, `nickname`, `email`, `phone`, `permissions`, `status`, etc.)

**CSS classes (from admin.css, automatically loaded by _head.php):**
- `.view-container` — Main page wrapper
- `.view-toolbar` / `.view-title` — Top toolbar bar
- `.card` / `.card-title` — Content card container
- `.view-actions` — Button group area

**JS libraries (from _foot.php):**
- Layui 2.8+ (layer, form, table, upload, element, etc.)
- jQuery (via Layui)
- `request(url, options)` from `config.js` — wrapper around fetch with auto Token
- `successMsg()` / `errorMsg()` from `common.js`
- `logout()` from `config.js`
- `config.js` globals: `CONFIG.baseUrl`, `CONFIG.apiUrl`

### JS `request()` Function

```javascript
// GET request (auto-attaches Bearer token)
request('/api/admin/users', { method: 'GET' })
    .then(res => { /* res is parsed JSON */ });

// POST with JSON body
request('/api/admin/endpoint', {
    method: 'POST',
    data: { name: 'value', status: 1 }
}).then(res => {
    if (res.code === 0) { /* success */ }
});

// DELETE
request('/api/admin/endpoint/123', { method: 'DELETE' });
```

### AJAX with Layui

```javascript
layui.use(['layer'], function() {
    var layer = layui.layer;

    // Layui AJAX (alternative to request())
    layui.$.ajax({
        url: '/api/admin/your-endpoint',
        type: 'POST',
        headers: { 'Authorization': 'Bearer ' + getCookie('admin_token') },
        contentType: 'application/json',
        data: JSON.stringify({ key: 'value' }),
        success: function(res) {
            if (res.code === 0) layer.msg('成功', {icon: 1});
        }
    });
});
```

---

## Step 2: Add Menu Entry

File: `app/config/CrudConfig.php` → `getMenus()`

Add a menu item with `type` => `'view'` and `view` => `'{viewName}'`:

```php
public static function getMenus()
{
    return [
        // ... existing groups ...
        '系统管理' => [
            'icon' => 'layui-icon-set',
            'items' => [
                // ... existing items ...
                [
                    'name' => '系统设置',        // Display name in sidebar
                    'type' => 'view',            // MUST be 'view'
                    'view' => 'system-settings', // matches app/views/admin/system-settings.php
                    'icon' => 'layui-icon-set',
                ],
            ],
        ],
    ];
}
```

---

## Permission Model

View pages have their own permission model. The key is `view_{viewName}` with a single action `access`:

```json
{
    "view_system-settings": ["access"],
    "view_reports": ["access"]
}
```

- Super admin (ID=1) always passes permission checks
- Other admins must have `view_{viewName}.access` explicitly granted
- Permission check is done by `AdminViewController::render()` — you don't need to add it in your view file

If a non-super admin without permission tries to access the view, they see a 403 error page rendered by `AdminViewController::renderError()`.

---

## Complete Example: Statistics Dashboard

**`app/views/admin/stats-dashboard.php`:**

```php
<?php
$pageTitle = '统计仪表盘';
include __DIR__ . '/_head.php';
?>

<div class="view-container">

    <div class="view-toolbar">
        <div class="view-title">
            <i class="layui-icon layui-icon-chart" style="margin-right:6px;"></i>
            数据仪表盘
        </div>
        <div>
            <button class="layui-btn layui-btn-sm" onclick="refreshStats()">
                <i class="layui-icon layui-icon-refresh"></i> 刷新数据
            </button>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div style="display:flex; gap:16px; margin-bottom:20px;">
        <div class="card" style="flex:1;">
            <div class="card-title">用户总数</div>
            <div style="font-size:32px; font-weight:bold; color:#1E90FF;" id="stat-users">-</div>
        </div>
        <div class="card" style="flex:1;">
            <div class="card-title">文章总数</div>
            <div style="font-size:32px; font-weight:bold; color:#FF6B6B;" id="stat-articles">-</div>
        </div>
        <div class="card" style="flex:1;">
            <div class="card-title">今日浏览</div>
            <div style="font-size:32px; font-weight:bold; color:#4ECDC4;" id="stat-views">-</div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/_foot.php'; ?>

<script>
layui.use(['layer', 'element'], function() {
    window.layer = layui.layer;
});

function refreshStats() {
    Promise.all([
        request('/api/admin/stats/users', {method:'GET'}),
        request('/api/admin/stats/articles', {method:'GET'}),
        request('/api/admin/stats/views', {method:'GET'}),
    ]).then(([users, articles, views]) => {
        document.getElementById('stat-users').textContent    = users.data.count ?? '-';
        document.getElementById('stat-articles').textContent = articles.data.count ?? '-';
        document.getElementById('stat-views').textContent    = views.data.count ?? '-';
    });
}

// Load on init
refreshStats();
</script>

</body>
</html>
```

**Menu entry in `CrudConfig::getMenus()`:**
```php
[
    'name' => '数据仪表盘',
    'type' => 'view',
    'view' => 'stats-dashboard',
    'icon' => 'layui-icon-chart',
],
```

## Common Pitfalls

- **Don't call `exit`/`die`** — use `terminateRequest()` if needed. But in view files you rarely need either; the view is included, not routed.
- **Don't pass tokens in URLs** — auth is via Cookie (`admin_token`), automatically synced by `config.js`.
- **The view file is PHP-included**, not routed — you can use any PHP code (DB queries, external APIs, etc.). But for data fetching, prefer AJAX via `request()` so the page loads fast.
- **Path safety**: viewName is sanitized by `AdminViewController` — only `a-zA-Z0-9_-/` allowed, path traversal blocked.
- **Iframe context**: the page runs in an iframe under `index.html`. Links with `target="_parent"` break out of the iframe.
