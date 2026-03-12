# Flight Base 快速开始

## 5 分钟快速部署

### 0. 使用 CRUD 设计器 (推荐)

**不想写代码？** 

1. 部署好系统后，登录后台。
2. 进入「系统工具」->「CRUD 设计器」。
3. 拖拽配置表单、表格。
4. 点击「生成代码」，将代码复制到 `app/config/CrudConfig.php` 中。
5. 完成！🎉

### 1. 配置数据库

修改 `app/config/database.php`：

```php
return [
    'type' => 'mysql',
    'host' => 'localhost',
    'database' => 'flight_base',  // 修改为你的数据库名
    'username' => 'root',          // 修改为你的用户名
    'password' => 'root',          // 修改为你的密码
    'prefix' => 'og_',
];
```

### 2. 导入数据库

在 MySQL 中执行 `database.sql` 文件：

```sql
-- 或者在命令行执行
mysql -u root -p < database.sql
```

### 3. 配置 Web 服务器

**⚠️ 重要提示**：必须配置 URL 重写（伪静态），否则路由无法工作！

#### phpstudy 配置（推荐，最简单）

1. 打开 phpstudy → 网站管理 → 创建网站
2. **根目录**（必须指向 public）：
   ```
   d:\phpstudy_pro\WWW\other\flight_base\public
   ```
3. **域名**：`flight-base.test`
4. **PHP 版本**：7.4+ 或 8.x
5. 保存（phpstudy 会自动配置伪静态）

6. 修改 hosts 文件（`C:\Windows\System32\drivers\etc\hosts`）：
   ```
   127.0.0.1  flight-base.test
   ```

#### 手动配置（Nginx）

如果不使用 phpstudy，请参考 [SERVER_CONFIG.md](SERVER_CONFIG.md) 配置 nginx 伪静态：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

#### 手动配置（Apache）

项目已包含 `.htaccess`，确保：
1. Apache 启用了 `mod_rewrite` 模块
2. 虚拟主机设置了 `AllowOverride All`

### 4. 测试配置是否成功

先测试伪静态是否生效：

```bash
# 访问健康检查接口
curl http://flight-base.test/api/health
```

**正确返回**（说明伪静态配置成功）：
```json
{
  "code": 0,
  "msg": "System is healthy",
  "data": {
    "status": "healthy",
    "database": "ok"
  }
}
```

**如果返回 404 错误**：说明伪静态没有配置成功，请查看 [SERVER_CONFIG.md](SERVER_CONFIG.md)

### 5. 访问系统

- **后台登录**：http://flight-base.test/admin/login.html
- **默认账号**：admin
- **默认密码**：password

**🔐 安全提示**：首次登录后，请立即进入"系统管理 → 修改密码"修改默认密码！

### 6. 测试 API

#### 健康检查（无需登录）
```bash
curl http://flight-base.test/api/health
```

响应：
```json
{
  "code": 0,
  "msg": "System is healthy",
  "data": {
    "status": "healthy",
    "timestamp": 1234567890,
    "datetime": "2026-01-23 13:30:00",
    "database": "ok",
    "version": "1.0.0",
    "app": "Flight Base"
  }
}
```

#### 用户登录
```bash
curl -X POST http://flight-base.test/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "password",
    "type": "admin"
  }'
```

响应：
```json
{
  "code": 0,
  "msg": "登录成功",
  "data": {
    "token": "eyJpZCI6MSwidXNlcm5hbWUiOiJhZG1pbiIsInR5cGUiOiJhZG1pbiIsImV4cCI6MTY0MDI1NjAwMH0=",
    "userInfo": {
      "id": 1,
      "username": "admin",
      "nickname": "超级管理员",
      "type": "admin"
    }
  }
}
```

#### 获取用户信息（需要登录）
```bash
curl http://flight-base.test/api/info \
  -H "Authorization: YOUR_TOKEN_HERE"
```

## 开发新功能

### 示例：添加商品管理

#### 1. 创建数据表

```sql
CREATE TABLE `og_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT '商品名称',
  `price` decimal(10,2) NOT NULL COMMENT '价格',
  `stock` int(11) DEFAULT '0' COMMENT '库存',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1上架 0下架',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';

-- 注意：数据库表名有 og_ 前缀，但代码中使用时不需要前缀
-- Medoo 会自动添加配置的前缀
```

#### 2. 创建控制器

创建 `app/api/ProductController.php`：

```php
<?php
namespace App\Api;

use App\Middleware\AuthMiddleware;

class ProductController
{
    // 获取商品列表
    public static function list()
    {
        $db = db();
        
        $page = (int)getQuery('page', 1);
        $limit = (int)getQuery('limit', 10);
        
        // 注意：使用 'products' 不是 'og_products'
        // Medoo 会自动添加配置的 og_ 前缀
        $count = $db->count('products');
        
        $products = $db->select('products', '*', [
            'LIMIT' => [($page - 1) * $limit, $limit],
            'ORDER' => ['id' => 'DESC']
        ]);
        
        Flight::json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $products
        ]);
    }
    
    // 创建商品（需要管理员权限）
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        
        $data = [
            'name' => getPost('name'),
            'price' => getPost('price'),
            'stock' => getPost('stock', 0),
            'status' => getPost('status', 1),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->insert('products', $data);
        
        if ($result->rowCount() > 0) {
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }
}
```

#### 3. 添加路由

在 `app/bootstrap.php` 中添加：

```php
// 商品管理
Flight::route('GET /api/products', function(){
    \App\Api\ProductController::list();
});

Flight::route('POST /api/product', function(){
    \App\Api\ProductController::create();
});
```

#### 4. 测试接口

```bash
# 获取商品列表
curl http://flight-base.test/api/products

# 创建商品（需要 token）
curl -X POST http://flight-base.test/api/product \
  -H "Content-Type: application/json" \
  -H "Authorization: YOUR_TOKEN" \
  -d '{
    "name": "iPhone 15",
    "price": 5999.00,
    "stock": 100
  }'
```

## 常用操作

### 数据库查询

```php
$db = db();

// 注意：使用不带前缀的表名，Medoo 会自动添加 og_ 前缀
// 数据库中的表名是 og_users，代码中使用 users

// 查询所有
$users = $db->select('users', '*');

// 条件查询
$user = $db->get('users', '*', ['id' => 1]);

// 分页查询
$users = $db->select('users', '*', [
    'LIMIT' => [0, 10],
    'ORDER' => ['id' => 'DESC']
]);

// 插入
$db->insert('users', [
    'username' => 'test',
    'password' => hashPassword('123456')
]);

// 更新
$db->update('users', [
    'nickname' => '新昵称'
], [
    'id' => 1
]);

// 删除
$db->delete('users', ['id' => 1]);
```

### 返回 JSON

```php
// 成功
success(['user_id' => 1], '操作成功');

// 失败
error('用户不存在', 404);
```

### 获取请求数据

```php
// POST 数据
$username = getPost('username');
$data = getPost();  // 获取所有

// GET 参数
$page = getQuery('page', 1);  // 第二个参数是默认值

// 请求头
$token = getHeader('Authorization');
```

### 权限验证

```php
use App\Middleware\AuthMiddleware;

// 需要登录
Flight::route('GET /api/info', function(){
    AuthMiddleware::check();  // 验证登录
    // ... 业务逻辑
});

// 需要管理员权限
Flight::route('DELETE /api/user/@id', function($id){
    AuthMiddleware::checkAdmin();  // 验证管理员
    // ... 业务逻辑
});
```

## 开发带 Views 的页面

除了 API，框架也支持渲染 HTML 页面。下面演示如何创建一个带视图的文章列表页面。

### 示例：文章列表页面

#### 1. 创建视图文件

创建 `app/views/articles/list.php`：

```php
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? '文章列表'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; margin-bottom: 20px; }
        .article-list { list-style: none; }
        .article-item { 
            border-bottom: 1px solid #eee; 
            padding: 15px 0; 
            transition: background 0.3s;
        }
        .article-item:hover { background: #f9f9f9; padding: 15px; margin: 0 -15px; }
        .article-title { font-size: 18px; color: #333; margin-bottom: 8px; }
        .article-meta { color: #999; font-size: 14px; }
        .article-content { color: #666; margin-top: 8px; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #1E90FF; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover { background: #0066CC; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($title ?? '文章列表'); ?></h1>
        
        <?php if (empty($articles)): ?>
            <p>暂无文章</p>
        <?php else: ?>
            <ul class="article-list">
                <?php foreach ($articles as $article): ?>
                    <li class="article-item">
                        <div class="article-title">
                            <a href="/article/<?php echo $article['id']; ?>">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </div>
                        <div class="article-meta">
                            作者：<?php echo htmlspecialchars($article['author'] ?? '匿名'); ?> | 
                            发布时间：<?php echo $article['created_at']; ?>
                        </div>
                        <div class="article-content">
                            <?php echo htmlspecialchars(mb_substr($article['content'], 0, 100)); ?>...
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <a href="/" class="btn">返回首页</a>
    </div>
</body>
</html>
```

#### 2. 创建控制器

创建 `app/api/ArticleController.php`：

```php
<?php
namespace App\Api;

class ArticleController
{
    /**
     * 显示文章列表页面
     */
    public static function listPage()
    {
        $db = db();
        
        // 查询文章列表
        $articles = $db->select('og_articles', '*', [
            'status' => 1,
            'ORDER' => ['created_at' => 'DESC'],
            'LIMIT' => 20
        ]);
        
        // 渲染视图
        Flight::render('articles/list', [
            'title' => '最新文章',
            'articles' => $articles
        ]);
    }
    
    /**
     * 显示文章详情页面
     */
    public static function detailPage($id)
    {
        $db = db();
        
        $article = $db->get('og_articles', '*', [
            'id' => $id,
            'status' => 1
        ]);
        
        if (!$article) {
            Flight::render('errors/404', [
                'message' => '文章不存在'
            ]);
            return;
        }
        
        Flight::render('articles/detail', [
            'article' => $article
        ]);
    }
}
```

#### 3. 配置视图路径

在 `app/bootstrap.php` 中添加视图路径配置：

```php
// ==================== 基础配置 ====================
$appConfig = require __DIR__ . '/../app/config/app.php';

date_default_timezone_set($appConfig['timezone']);

// 配置视图路径
Flight::set('flight.views.path', __DIR__ . '/../app/views');

// ... 其他配置
```

#### 4. 添加路由

在 `app/bootstrap.php` 中添加路由：

```php
// ========== 前端页面路由 ==========

// 文章列表页
Flight::route('GET /articles', function(){
    \App\Api\ArticleController::listPage();
});

// 文章详情页
Flight::route('GET /article/@id', function($id){
    \App\Api\ArticleController::detailPage($id);
});
```

#### 5. 创建 404 错误页面（可选）

创建 `app/views/errors/404.php`：

```php
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>页面不存在</title>
    <style>
        body { 
            font-family: Arial; 
            text-align: center; 
            padding: 100px; 
            background: #f5f5f5; 
        }
        h1 { font-size: 72px; color: #999; }
        p { font-size: 18px; color: #666; }
        a { color: #1E90FF; text-decoration: none; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p><?php echo htmlspecialchars($message ?? '页面不存在'); ?></p>
    <p><a href="/">返回首页</a></p>
</body>
</html>
```

#### 6. 测试访问

**✅ 框架已包含完整示例代码！**

浏览器访问：
- **文章列表页面**：`http://your-domain.com/articles`
- **文章详情页面**：`http://your-domain.com/article/1`
- **文章列表 API**：`http://your-domain.com/api/articles`
- **文章详情 API**：`http://your-domain.com/api/article/1`

**注意**：
- 如果看到"暂无文章"，说明数据库中没有文章数据
- `database.sql` 中已包含示例文章数据
- 视图文件位于：`app/views/articles/`
- 控制器位于：`app/api/ArticleController.php`

### 使用模板引擎（可选）

如果需要更强大的模板功能，可以集成 Twig 或 Smarty：

#### 安装 Twig

```bash
composer require twig/twig
```

#### 配置 Twig

在 `app/bootstrap.php` 中：

```php
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

// 注册 Twig
Flight::register('view', Environment::class, [
    new FilesystemLoader(__DIR__ . '/../app/views')
], function($twig){
    $twig->addGlobal('app_name', config('name'));
});
```

#### 使用 Twig 渲染

```php
Flight::route('GET /articles', function(){
    $db = db();
    $articles = $db->select('og_articles', '*');
    
    echo Flight::view()->render('articles/list.twig', [
        'articles' => $articles
    ]);
});
```

### 混合开发建议

在实际项目中，你可以：

1. **API 接口**（`/api/*`）：返回 JSON，供前端框架（Vue/React）或移动端使用
2. **管理后台**（`/admin/*`）：使用 Layui + API 接口
3. **前台页面**（其他路由）：使用 Flight 视图渲染，适合 SEO 页面

这样既能快速开发，又能满足不同场景的需求！

## 下一步

- 查看 [README.md](README.md) 了解更多功能
- 根据你的需求添加新的控制器和路由
- 参考 Medoo 文档学习数据库操作：https://medoo.in/
- 参考 Flight 文档学习路由功能：https://flightphp.com/

祝你开发愉快！🚀
