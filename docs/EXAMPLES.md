# 开发示例

本文档提供完整的开发示例，帮助你快速理解 Flight Base 框架的使用方法。

---

## 示例 0：添加新功能模块（最重要！）

> **场景**：添加一个"商品管理"功能，自动生成菜单和 CRUD 页面

### 步骤 1：在菜单配置中添加

编辑 `app/config/CrudConfig.php` 的 `getMenus()` 方法：

```php
public static function getMenus()
{
    return [
        '仪表盘' => [
            'icon' => 'layui-icon-home',
            'items' => [
                ['name' => '数据统计', 'page' => 'dashboard', 'icon' => 'layui-icon-chart']
            ]
        ],
        '商品管理' => [  // 新增分组
            'icon' => 'layui-icon-cart-simple',  // 父级图标
            'items' => [
                ['name' => '商品列表', 'page' => 'products', 'icon' => 'layui-icon-cart'],
                ['name' => '分类管理', 'page' => 'categories', 'icon' => 'layui-icon-template']
            ]
        ],
        '系统管理' => [
            'icon' => 'layui-icon-set',
            'items' => [
                ['name' => '用户管理', 'page' => 'users', 'icon' => 'layui-icon-user']
            ]
        ]
    ];
}
```

**说明**：
- `icon`：父级菜单图标（多项分组时显示）
- `items`：子菜单数组
- 只有 1 个子项 → 显示为一级菜单（不下拉）
- 有 2+ 个子项 → 显示为分组菜单（有下拉）

### 步骤 2：添加配置方法

在同一文件中添加 `products()` 方法：

```php
public static function products()
{
    return [
        'page' => [
            'title' => '商品管理',
            'icon' => 'layui-icon-cart'
        ],
        
        'table' => [
            'url' => '/api/admin/products',
            'cols' => [
                ['field' => 'id', 'title' => 'ID', 'width' => 80],
                ['field' => 'name', 'title' => '商品名称', 'minWidth' => 200],
                ['field' => 'price', 'title' => '价格', 'width' => 120],
                ['field' => 'stock', 'title' => '库存', 'width' => 100],
                ['toolbar' => '#toolBar']
            ]
        ],
        
        'form' => [
            ['type' => 'input', 'name' => 'name', 'label' => '商品名称', 'required' => true],
            ['type' => 'number', 'name' => 'price', 'label' => '价格', 'required' => true],
            ['type' => 'number', 'name' => 'stock', 'label' => '库存', 'required' => true]
        ],
        
        'api' => [
            'list' => '/api/admin/products',
            'add' => '/api/admin/product',
            'edit' => '/api/admin/product',
            'delete' => '/api/admin/product'
        ]
    ];
}
```

### 步骤 3：创建控制器

创建 `app/api/admin/ProductController.php`：

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class ProductController
{
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        $page = (int)getQuery('page', 1);
        $limit = (int)getQuery('limit', 10);
        
        $count = $db->count('products');
        $products = $db->select('products', '*', [
            'LIMIT' => [($page - 1) * $limit, $limit],
            'ORDER' => ['id' => 'DESC']
        ]);
        
        layuiTable($products, $count);
    }
    
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        $data = [
            'name' => cleanInput(getPost('name')),
            'price' => getPost('price'),
            'stock' => getPost('stock'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->insert('products', $data);
        
        if ($result->rowCount() > 0) {
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }
    
    // update() 和 delete() 方法类似...
}
```

### 步骤 4：添加路由

编辑 `app/bootstrap.php`，添加路由：

```php
Flight::route('GET /api/admin/products', function(){
    \App\Api\Admin\ProductController::list();
});

Flight::route('POST /api/admin/product', function(){
    \App\Api\Admin\ProductController::create();
});

Flight::route('POST /api/admin/product/@id', function($id){
    \App\Api\Admin\ProductController::update($id);
});

Flight::route('DELETE /api/admin/product/@id', function($id){
    \App\Api\Admin\ProductController::delete($id);
});
```

### 完成！

刷新后台页面，左侧菜单会自动出现"商品管理"，点击即可使用完整的 CRUD 功能！

**关键点**：
- ✅ 菜单自动生成，无需修改 HTML
- ✅ 前端自动渲染，无需写 JavaScript
- ✅ 只需配置 + 控制器，5 分钟搞定

---

## 示例 1：文章浏览量统计

> **场景**：每次访问文章详情页时，自动增加浏览量

### 实现代码

**文件**：`app/api/ArticleController.php`

```php
/**
 * 显示文章详情页面
 * 
 * 功能说明：
 * 1. 查询文章信息（仅已发布）
 * 2. 自动增加浏览量（views +1）
 * 3. 渲染详情页面
 */
public static function detailPage($id)
{
    $db = db();
    
    // 1. 查询文章
    $article = $db->get('articles', '*', [
        'id' => $id,
        'is_published' => 1
    ]);
    
    // 2. 文章不存在，显示 404
    if (!$article) {
        Flight::render('errors/404', [
            'message' => '文章不存在'
        ]);
        return;
    }
    
    // 3. 浏览量 +1（使用 Medoo 的原子操作）
    $db->update('articles', [
        'views[+]' => 1  // 使用 [+] 表示字段值 +1
    ], [
        'id' => $id
    ]);
    
    // 4. 更新数组中的浏览量（用于页面显示）
    $article['views'] = ($article['views'] ?? 0) + 1;
    
    // 5. 渲染视图
    Flight::render('articles/detail', [
        'article' => $article
    ]);
}
```

### 关键知识点

#### 1. 数据库原子操作

```php
// ✅ 推荐：使用原子操作（线程安全）
$db->update('articles', [
    'views[+]' => 1  // views = views + 1
], [
    'id' => $id
]);

// ❌ 不推荐：先查询再更新（存在并发问题）
$article = $db->get('articles', 'views', ['id' => $id]);
$db->update('articles', [
    'views' => $article['views'] + 1
], [
    'id' => $id
]);
```

**原子操作支持**：
- `[+]` - 加法（`field = field + value`）
- `[-]` - 减法（`field = field - value`）
- `[*]` - 乘法（`field = field * value`）
- `[/]` - 除法（`field = field / value`）

#### 2. 视图渲染

```php
// 渲染视图文件：app/views/articles/detail.php
Flight::render('articles/detail', [
    'article' => $article  // 传递变量到视图
]);
```

**视图文件中访问变量**：
```php
<!-- app/views/articles/detail.php -->
<h1><?php echo $article['title']; ?></h1>
<p>浏览量：<?php echo $article['views']; ?></p>
```

#### 3. 404 处理

```php
if (!$article) {
    Flight::render('errors/404', [
        'message' => '文章不存在'
    ]);
    return;  // 终止后续执行
}
```

---

## 示例 2：带分页的文章列表

> **场景**：显示文章列表，支持分页、搜索、排序

### 实现代码

```php
/**
 * 文章列表页面
 */
public static function listPage()
{
    $db = db();
    
    // 1. 获取查询参数
    $page = (int)getQuery('page', 1);
    $limit = 10;
    $keyword = getQuery('keyword', '');
    
    // 2. 构建查询条件
    $where = ['is_published' => 1];
    
    if ($keyword) {
        $where['OR'] = [
            'title[~]' => $keyword,      // LIKE '%keyword%'
            'content[~]' => $keyword
        ];
    }
    
    // 3. 查询总数（用于分页）
    $total = $db->count('articles', $where);
    
    // 4. 查询文章列表
    $where['ORDER'] = ['created_at' => 'DESC'];  // 按创建时间倒序
    $where['LIMIT'] = [($page - 1) * $limit, $limit];  // 分页
    
    $articles = $db->select('articles', [
        'id',
        'title',
        'author',
        'cover',
        'publish_date',
        'views',
        'created_at'
    ], $where);
    
    // 5. 渲染视图
    Flight::render('articles/list', [
        'title' => '最新文章',
        'articles' => $articles,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'keyword' => $keyword
    ]);
}
```

### 关键知识点

#### 1. 分页计算

```php
$page = 1;   // 当前页
$limit = 10; // 每页条数

// LIMIT offset, count
$where['LIMIT'] = [
    ($page - 1) * $limit,  // offset（跳过多少条）
    $limit                  // count（返回多少条）
];

// 示例：
// page=1: LIMIT 0, 10  （第 1-10 条）
// page=2: LIMIT 10, 10 （第 11-20 条）
// page=3: LIMIT 20, 10 （第 21-30 条）
```

#### 2. 模糊查询

```php
// LIKE '%keyword%'（包含）
$where = [
    'title[~]' => $keyword
];

// LIKE 'keyword%'（以...开头）
$where = [
    'title[~]' => $keyword . '%'
];

// LIKE '%keyword'（以...结尾）
$where = [
    'title[~]' => '%' . $keyword
];

// OR 条件（多字段搜索）
$where = [
    'OR' => [
        'title[~]' => $keyword,
        'content[~]' => $keyword,
        'author[~]' => $keyword
    ]
];
```

#### 3. 排序

```php
// 单字段排序
$where['ORDER'] = ['created_at' => 'DESC'];  // 降序
$where['ORDER'] = ['created_at' => 'ASC'];   // 升序

// 多字段排序
$where['ORDER'] = [
    'is_published' => 'DESC',  // 先按发布状态排序
    'created_at' => 'DESC'      // 再按创建时间排序
];
```

---

## 示例 3：API 接口（返回 JSON）

> **场景**：提供 RESTful API 接口，返回 JSON 格式数据

### 实现代码

```php
/**
 * API: 获取文章详情（JSON）
 */
public static function detailApi($id)
{
    $db = db();
    
    // 查询文章
    $article = $db->get('articles', '*', [
        'id' => $id,
        'is_published' => 1
    ]);
    
    // 文章不存在
    if (!$article) {
        error('文章不存在', 404);
        return;
    }
    
    // 浏览量 +1
    $db->update('articles', [
        'views[+]' => 1
    ], [
        'id' => $id
    ]);
    
    // 更新浏览量
    $article['views'] = ($article['views'] ?? 0) + 1;
    
    // 返回成功响应
    success($article);
}
```

### 关键知识点

#### 1. 成功响应

```php
// 返回成功（默认 code=0）
success($data);

// 返回成功（自定义消息）
success($data, '操作成功');

// 响应格式：
{
    "code": 0,
    "msg": "success",
    "data": { ... }
}
```

#### 2. 错误响应

```php
// 返回错误（默认 code=1）
error('错误信息');

// 返回错误（自定义 code）
error('文章不存在', 404);

// 响应格式：
{
    "code": 404,
    "msg": "文章不存在",
    "data": null
}
```

#### 3. 路由定义

**文件**：`app/bootstrap.php`

```php
// GET 请求
Flight::route('GET /api/article/@id', function($id){
    \App\Api\ArticleController::detailApi($id);
});

// POST 请求
Flight::route('POST /api/article', function(){
    \App\Api\ArticleController::createApi();
});

// PUT 请求（更新）
Flight::route('PUT /api/article/@id', function($id){
    \App\Api\ArticleController::updateApi($id);
});

// DELETE 请求
Flight::route('DELETE /api/article/@id', function($id){
    \App\Api\ArticleController::deleteApi($id);
});
```

---

## 示例 4：表单提交处理

> **场景**：处理用户提交的表单数据，包含验证、清洗、入库

### 实现代码

```php
/**
 * 创建文章
 */
public static function create()
{
    $db = db();
    
    // 1. 获取表单数据
    $title = getPost('title');
    $author = getPost('author');
    $content = getPost('content');
    $cover = getPost('cover', '');
    
    // 2. 数据验证
    if (empty($title)) {
        error('标题不能为空');
        return;
    }
    
    if (mb_strlen($title) > 200) {
        error('标题不能超过 200 个字符');
        return;
    }
    
    if (empty($author)) {
        error('作者不能为空');
        return;
    }
    
    // 3. 清洗数据（防止 XSS）
    $title = cleanInput($title);
    $author = cleanInput($author);
    
    // 4. 准备数据
    $data = [
        'title' => $title,
        'author' => $author,
        'content' => $content,  // 富文本内容不清洗
        'cover' => $cover,
        'publish_date' => date('Y-m-d'),
        'is_published' => 1,
        'views' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // 5. 插入数据库
    $result = $db->insert('articles', $data);
    
    // 6. 检查结果
    if ($result->rowCount() > 0) {
        $id = $db->id();  // 获取插入的 ID
        writeLog('创建文章：' . $title, 'info');
        success(['id' => $id], '创建成功');
    } else {
        error('创建失败');
    }
}
```

### 关键知识点

#### 1. 获取请求参数

```php
// GET 参数
$page = getQuery('page', 1);      // 默认值 1
$keyword = getQuery('keyword');   // 无默认值

// POST 参数
$title = getPost('title');
$author = getPost('author', '匿名');  // 默认值 '匿名'

// 原始方式（不推荐）
$page = Flight::request()->query['page'] ?? 1;
$title = Flight::request()->data['title'] ?? '';
```

#### 2. 数据验证

```php
// 必填验证
if (empty($title)) {
    error('标题不能为空');
    return;
}

// 长度验证
if (mb_strlen($title) > 200) {
    error('标题不能超过 200 个字符');
    return;
}

// 格式验证
if (!validateEmail($email)) {
    error('邮箱格式不正确');
    return;
}

if (!validatePhone($phone)) {
    error('手机号格式不正确');
    return;
}
```

#### 3. 数据清洗

```php
// XSS 防护（清理 HTML 标签）
$title = cleanInput($title);

// 注意：富文本内容不要清洗
$content = getPost('content');  // 保留 HTML 标签
```

#### 4. 插入数据

```php
// 插入记录
$result = $db->insert('articles', [
    'title' => $title,
    'author' => $author,
    'created_at' => date('Y-m-d H:i:s')
]);

// 检查是否成功
if ($result->rowCount() > 0) {
    $id = $db->id();  // 获取自增 ID
    success(['id' => $id], '创建成功');
} else {
    error('创建失败');
}
```

---

## 示例 5：更新数据

> **场景**：更新文章信息

### 实现代码

```php
/**
 * 更新文章
 */
public static function update($id)
{
    $db = db();
    
    // 1. 检查文章是否存在
    $exists = $db->has('articles', ['id' => $id]);
    if (!$exists) {
        error('文章不存在', 404);
        return;
    }
    
    // 2. 获取更新数据
    $data = [];
    
    if (getPost('title')) {
        $data['title'] = cleanInput(getPost('title'));
    }
    
    if (getPost('author')) {
        $data['author'] = cleanInput(getPost('author'));
    }
    
    if (getPost('content')) {
        $data['content'] = getPost('content');
    }
    
    if (getPost('cover') !== null) {
        $data['cover'] = getPost('cover');
    }
    
    // 3. 没有更新内容
    if (empty($data)) {
        error('没有需要更新的内容');
        return;
    }
    
    // 4. 更新数据
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    $db->update('articles', $data, [
        'id' => $id
    ]);
    
    // 5. 记录日志
    writeLog('更新文章 ID: ' . $id, 'info');
    
    success([], '更新成功');
}
```

### 关键知识点

#### 1. 检查记录是否存在

```php
// 检查是否存在
$exists = $db->has('articles', ['id' => $id]);

if (!$exists) {
    error('记录不存在', 404);
    return;
}
```

#### 2. 部分字段更新

```php
// 只更新提交的字段
$data = [];

if (getPost('title')) {
    $data['title'] = getPost('title');
}

if (getPost('author')) {
    $data['author'] = getPost('author');
}

// 更新
$db->update('articles', $data, ['id' => $id]);
```

#### 3. 更新时间戳

```php
// 自动更新 updated_at
$data['updated_at'] = date('Y-m-d H:i:s');

// 或使用数据库函数
$data['updated_at'] = Medoo::raw('NOW()');
```

---

## 示例 6：删除数据

> **场景**：删除文章

### 实现代码

```php
/**
 * 删除文章
 */
public static function delete($id)
{
    $db = db();
    
    // 1. 检查文章是否存在
    $article = $db->get('articles', ['id', 'title'], ['id' => $id]);
    
    if (!$article) {
        error('文章不存在', 404);
        return;
    }
    
    // 2. 删除文章
    $db->delete('articles', [
        'id' => $id
    ]);
    
    // 3. 记录日志
    writeLog('删除文章：' . $article['title'], 'info');
    
    success([], '删除成功');
}
```

### 关键知识点

#### 1. 软删除 vs 硬删除

```php
// 硬删除（物理删除）
$db->delete('articles', ['id' => $id]);

// 软删除（标记删除）
$db->update('articles', [
    'is_deleted' => 1,
    'deleted_at' => date('Y-m-d H:i:s')
], [
    'id' => $id
]);
```

#### 2. 批量删除

```php
// 删除多条记录
$db->delete('articles', [
    'id' => [1, 2, 3, 4, 5]  // WHERE id IN (1,2,3,4,5)
]);

// 删除满足条件的记录
$db->delete('articles', [
    'created_at[<]' => '2020-01-01'  // 删除 2020 年之前的
]);
```

---

## 辅助函数速查

### 数据库操作

```php
$db = db();  // 获取数据库实例
```

### 请求参数

```php
getQuery('key', 'default');   // GET 参数
getPost('key', 'default');    // POST 参数
```

### 响应

```php
success($data, 'msg');  // 成功响应
error('msg', 500);      // 错误响应
```

### 日志

```php
writeLog('message', 'info');     // info 日志
writeLog('message', 'error');    // error 日志
writeLog('message', 'warning');  // warning 日志
```

### 安全

```php
cleanInput($str);              // XSS 防护
validateEmail($email);         // 邮箱验证
validatePhone($phone);         // 手机号验证
validateUsername($username);   // 用户名验证
validatePassword($password);   // 密码强度验证
```

---

## 更多示例

查看以下文档了解更多：

- [COMPLEX_QUERY.md](COMPLEX_QUERY.md) - 复杂查询（JOIN、事务、聚合等）
- [ADMIN_DEV.md](ADMIN_DEV.md) - 后台开发（配置驱动 CRUD）
- [SECURITY.md](SECURITY.md) - 安全措施（SQL 注入、XSS、CSRF 等）

---

**祝你开发愉快！** 🚀
