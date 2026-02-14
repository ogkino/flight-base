# Flight Base 框架 v2.0

> **轻量级 · 配置驱动 · AI 友好 · 快速开发**

一个基于 **Flight + Medoo + Layui** 的超轻量级 PHP 后台管理系统基础框架。

---

## ✨ 核心特点

- ✅ **配置驱动**：后端配置 → 前端自动渲染 CRUD（零手写代码）
- ✅ **可视化设计**：内置 CRUD 设计器，拖拽生成代码，零代码开发 (v2.3)
- ✅ **自动菜单**：菜单自动从配置生成，支持父级图标、智能分组
- ✅ **权限系统**：基于 JSON 的轻量级权限管理，精确到 CRUD 操作，自动过滤菜单
- ✅ **AI 极度友好**：结构化配置，AI 轻松生成完整功能模块
- ✅ **超级轻量**：核心代码 < 1000 行，配置驱动让代码量减少 80%
- ✅ **无需打包**：使用 Layui，修改即刻生效，开发体验极致
- ✅ **快速开发**：5 分钟完成完整 CRUD 模块
- ✅ **安全完善**：10+ 安全措施，生产环境可用
- ✅ **功能强大**：支持 JOIN、事务等复杂业务
- ✅ **丰富组件**：内置评分、滑块、颜色选择器、图标选择器、标签输入等 20+ 种常用组件 (v2.4)
- ✅ **细粒度控制**：支持字段在新增/编辑模式下的独立状态控制 (v2.3)

---

## 📖 技术栈

| 技术 | 版本 | 用途 |
|------|------|------|
| **PHP** | >= 7.4 | 后端语言 |
| **Flight** | ^3.0 | 轻量级路由框架 |
| **Medoo** | ^2.1 | 轻量级 ORM |
| **Layui** | 2.8+ | 无需打包的前端 UI 库 |

---

## 📁 目录结构

```
flight-base/
├── app/                          # 应用核心目录
│   ├── api/                      # API 控制器
│   │   ├── admin/                # 后台管理 API
│   │   │   ├── ArticleController.php  # 文章管理（示例）
│   │   │   ├── AuthController.php     # 管理员认证
│   │   │   ├── ConfigController.php   # 配置接口（配置驱动核心）
│   │   │   ├── UploadController.php   # 文件上传
│   │   │   └── UserController.php     # 用户管理（示例）
│   │   ├── ArticleController.php      # 前端文章接口（Views 示例）
│   │   ├── AuthController.php         # 前端用户认证
│   │   └── HealthController.php       # 健康检查
│   ├── config/                   # 配置文件
│   │   ├── app.php               # 应用配置
│   │   ├── cors.php              # 跨域配置
│   │   ├── CrudConfig.php        # 🔥 CRUD 配置（配置驱动核心）
│   │   └── database.php          # 数据库配置
│   ├── helpers/                  # 辅助函数
│   │   ├── env.php               # 环境变量读取
│   │   ├── functions.php         # 全局函数
│   │   └── security.php          # 安全函数（XSS、CSRF、限流等）
│   ├── middleware/               # 中间件
│   │   ├── AuthMiddleware.php    # 权限验证中间件
│   │   └── CorsMiddleware.php    # 跨域中间件
│   └── views/                    # 视图模板（可选，用于非 API 页面）
│       ├── articles/             # 文章页面示例
│       ├── errors/               # 错误页面
│       └── home/                 # 首页
├── public/                       # Web 根目录（部署时指向这里）
│   ├── admin/                    # 后台管理前端
│   │   ├── assets/
│   │   │   ├── css/
│   │   │   │   └── admin.css     # 后台样式
│   │   │   └── js/
│   │   │       ├── common.js     # 公共函数
│   │   │       ├── config.js     # 前端配置
│   │   │       └── crud-renderer.js  # 🔥 通用渲染器（配置驱动核心）
│   │   ├── index.html            # 后台主页（单页应用）
│   │   └── login.html            # 登录页
│   ├── uploads/                  # 上传文件目录
│   ├── index.php                 # 🔥 主入口文件（路由定义）
│   └── .htaccess                 # Apache 伪静态配置
├── runtime/                      # 运行时目录
│   └── logs/                     # 日志文件
├── vendor/                       # Composer 依赖
├── .env                          # 环境变量配置（需创建）
├── .gitignore                    # Git 忽略文件
├── composer.json                 # Composer 配置
├── example_db.sql                  # 数据库初始化脚本
├── docs/                         # 📖 文档目录
│   ├── QUICKSTART.md             # 快速部署
│   ├── ARCHITECTURE.md           # 架构说明（必读！）
│   ├── ADMIN_DEV.md              # 后台开发指南
│   ├── FIELD_TYPES.md            # 字段类型参考
│   ├── SECURITY.md               # 安全指南
│   ├── COMPLEX_QUERY.md          # 复杂查询示例
│   ├── DEPLOY.md                 # 生产部署（单体）
│   ├── SEPARATION_DEPLOY.md      # 三端分离部署
│   ├── SERVER_CONFIG.md          # 服务器配置
│   ├── DATABASE_UPGRADE.md       # 数据库升级说明
│   ├── BUGFIX_2.1.4.md           # Bug 修复说明
│   └── CHANGELOG.md              # 更新日志
└── README.md                     # 📖 本文档（快速开始）
```

---

## 🚀 快速开始

### 1. 安装依赖

```bash
composer install
```

### 2. 配置数据库

复制 `.env` 模板：

```bash
cp ENV_TEMPLATE.txt .env
```

编辑 `.env` 文件：

```ini
# 数据库配置
DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=flight_base
DB_USER=root
DB_PASS=root
DB_PREFIX=og_

# 应用配置
APP_DEBUG=true
APP_TIMEZONE=Asia/Shanghai

# JWT 密钥（修改为随机字符串）
JWT_SECRET=your-secret-key-change-this
```

### 3. 导入数据库

在 MySQL 中执行 `example_db.sql` 文件创建数据表。

```sql
-- 包含以下表：
-- og_admin      管理员表
-- og_users      用户表
-- og_articles   文章表（示例）
```

### 4. 配置 Web 服务器

**⚠️ 重要**：Flight 框架需要配置 URL 重写（伪静态）才能正常工作！

#### phpstudy 配置（测试用，实际以线上环境为主）

1. 打开 phpstudy → 网站管理 → 创建网站
2. **根目录**：`d:\phpstudy\WWW\flight_base\public`（重要！指向 public 目录）
3. **域名**：`flight-base.test`
4. **PHP 版本**：7.4 或 8.x
5. 保存（phpstudy 会自动配置伪静态）
6. 修改 hosts：`C:\Windows\System32\drivers\etc\hosts`
   ```
   127.0.0.1  flight-base.test
   ```

#### Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # 根目录指向 public（重要！）
    root /path/to/flight_base/public;
    index index.php index.html;
    
    # URL 重写规则（核心配置）
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
    
    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }
}
```

**详细配置说明**：查看 [SERVER_CONFIG.md](docs/SERVER_CONFIG.md)

### 5. 测试配置

先测试伪静态是否生效：

```bash
curl http://your-domain.com/api/health
```

**正确返回**（伪静态配置成功）：
```json
{
  "code": 0,
  "msg": "System is healthy"
}
```

**如果返回 404**：说明伪静态未配置，请查看 [SERVER_CONFIG.md](docs/SERVER_CONFIG.md)

### 6. 访问系统

- **后台登录**：`http://your-domain.com/admin/login.html`
- **默认账号**：admin / password
- **前端首页**：`http://your-domain.com/`（Views 示例）

---

## 🔥 配置驱动快速开发

**v2.0 核心功能**：零手写代码，通过配置自动生成完整 CRUD！

### 示例：30 秒添加商品管理

#### 1. 在 `app/config/CrudConfig.php` 添加配置

```php
<?php
public static function products()
{
    return [
        // 页面信息
        'page' => [
            'title' => '商品管理',
            'icon' => 'layui-icon-cart',
            'page' => 'products' 
        ],
        
        // 表格配置
        'table' => [
            'url' => '/api/admin/products',
            'actionsWidth' => 250, // 设置操作列宽度
            'cols' => [
                ['field' => 'id', 'title' => 'ID', 'width' => 80],
                ['field' => 'name', 'title' => '商品名', 'minWidth' => 200],
                ['field' => 'price', 'title' => '价格', 'width' => 120],
                ['field' => 'stock', 'title' => '库存', 'width' => 100]
            ]
        ],
        
        // 定义操作按钮
        'actions' => [
            [
                'text' => '编辑',
                'action' => 'edit',
                'icon' => 'layui-icon-edit',
                'class' => 'layui-btn-normal',
                'permission' => 'update' // 权限标识
            ],
            [
                'text' => '删除',
                'action' => 'delete',
                'icon' => 'layui-icon-delete',
                'class' => 'layui-btn-danger',
                'permission' => 'delete' // 权限标识
            ],
            [
                'text' => '自定义按钮',
                'action' => 'preview',
                'icon' => 'layui-icon-file',
                'class' => 'layui-btn-warm',
                'type' => 'iframe', // 使用 iframe 弹窗 或 page 跳转
                'url' => '/product/{id}',
                'width' => '1600px', // 弹窗宽度
                'height' => '80%'    // 弹窗高度
            ]
        ],

        'search' => [
            [
                'type' => 'input',
                'name' => 'keyword',
                'placeholder' => '搜索商品',
                'width' => 250
            ]
        ],
            
        'toolbar' => [
            [
                'text' => '新增商品',
                'icon' => 'layui-icon-add-1',
                'class' => 'layui-btn-normal',
                'action' => 'add',
                'permission' => 'create' // 权限标识
            ],
            [
                'text' => '导出数据',
                'icon' => 'layui-icon-export',
                'class' => 'layui-btn-warm',
                'action' => 'export',
                'permission' => 'export' // 权限标识
            ]
        ],
        
        // 表单配置（支持 15+ 字段类型）
        'form' => [
            ['type' => 'input', 'name' => 'name', 'label' => '商品名', 'required' => true],
            ['type' => 'number', 'name' => 'price', 'label' => '价格', 'min' => 0, 'step' => 0.01],
            ['type' => 'image', 'name' => 'cover', 'label' => '商品图片', 'tip' => '建议尺寸：800x600'],
            ['type' => 'textarea', 'name' => 'description', 'label' => '描述', 'rows' => 5],
            ['type' => 'switch', 'name' => 'status', 'label' => '状态', 'text' => '上架|下架']
        ],
        
        // API 配置
        'api' => [
            'list' => '/api/admin/products',
            'add' => '/api/admin/product',
            'edit' => '/api/admin/product/{id}',
            'delete' => '/api/admin/product/{id}'
        ]
    ];
}
```

#### 2. 创建控制器 `app/api/admin/ProductController.php`

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class ProductController
{
    public static function list() {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'list');
        $db = db();
        $products = $db->select('products', '*');
        layuiTable($products, count($products));
    }
    
    public static function create() {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'create');
        $db = db();
        $db->insert('products', [
            'name' => getPost('name'),
            'price' => getPost('price'),
            'cover' => getPost('cover'),
            'description' => getPost('description'),
            'status' => getPost('status', 0)
        ]);
        success(['id' => $db->id()], '创建成功');
    }
    
    public static function update($id) {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'update');
        $db = db();
        $db->update('products', [
            'name' => getPost('name'),
            'price' => getPost('price'),
            'cover' => getPost('cover'),
            'description' => getPost('description'),
            'status' => getPost('status', 0)
        ], ['id' => $id]);
        success([], '更新成功');
    }
    
    public static function delete($id) {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'delete');
        $db = db();
        $db->delete('products', ['id' => $id]);
        success([], '删除成功');
    }

    public static function export() {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'list');
        // ... 导出逻辑 ...
    }
}
```

#### 3. 注册路由 `public/index.php`

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

#### 4. 在 `app/config/CrudConfig.php` 的 getMenus() 里添加菜单


#### ✅ 完成！

刷新页面，点击"商品列表"，自动生成：
- ✅ 表格（分页、排序）
- ✅ 搜索框
- ✅ 新增/编辑弹窗（宽度 800px，美观）
- ✅ 删除确认
- ✅ 文件上传（自动处理）
- ✅ 数据导出（action="export"）
- ✅ 自定义按钮（支持 iframe 弹窗、页面跳转）
- ✅ 动态下拉框（支持从 API 获取选项）

---

## 🎨 支持的字段类型（15+ 种）

| 类型 | 说明 | 配置示例 |
|------|------|----------|
| **input** | 文本输入框 | `['type' => 'input', 'name' => 'title', 'label' => '标题']` |
| **password** | 密码输入框 | `['type' => 'password', 'name' => 'password']` |
| **textarea** | 多行文本框 | `['type' => 'textarea', 'name' => 'content', 'rows' => 5]` |
| **editor** | 富文本编辑器 | `['type' => 'editor', 'name' => 'content', 'height' => '400px']` |
| **number** | 数字输入框 | `['type' => 'number', 'name' => 'price', 'min' => 0, 'step' => 0.01]` |
| **radio** | 单选框 | `['type' => 'radio', 'options' => [...]]` |
| **select** | 下拉框（支持动态数据） | `['type' => 'select', 'options' => [...]]` |
| **switch** | 开关 | `['type' => 'switch', 'name' => 'status', 'text' => 'ON\|OFF']` |
| **date** | 日期选择器 | `['type' => 'date', 'name' => 'publish_date']` |
| **datetime** | 日期时间选择器 | `['type' => 'datetime', 'name' => 'created_at']` |
| **time** | 时间选择器 | `['type' => 'time', 'name' => 'open_time']` |
| **timestamp** | 时间戳 (自动转换) | `['type' => 'timestamp', 'name' => 'updated_at']` |
| **upload** | 文件上传 | `['type' => 'upload', 'name' => 'file', 'uploadUrl' => '/api/admin/upload']` |
| **image** | 图片上传（带预览） | `['type' => 'image', 'name' => 'cover', 'tip' => '建议尺寸：800x600']` |
| **color** | 颜色选择器 | `['type' => 'color', 'name' => 'theme_color']` |
| **slider** | 滑块 | `['type' => 'slider', 'name' => 'volume', 'min' => 0, 'max' => 100]` |

**详细文档**：
- 完整开发示例：[EXAMPLES.md](docs/EXAMPLES.md)
- 字段类型完整参考：[FIELD_TYPES.md](docs/FIELD_TYPES.md)
- 开发指南：[ADMIN_DEV.md](docs/ADMIN_DEV.md)

---

## 🔒 安全性

虽然轻量，但安全措施完善，适合生产环境。

### 已实现的安全措施

- ✅ **JWT Token 认证**：无状态，易扩展，7天自动过期
- ✅ **权限中间件**：自动验证用户身份和权限
- ✅ **SQL 注入防护**：Medoo 预处理 + 额外检测
- ✅ **XSS 防护**：`cleanInput()` 过滤 + 输出转义
- ✅ **CSRF 防护**：Token 验证
- ✅ **密码加密**：bcrypt 强加密（`password_hash`）
- ✅ **输入验证**：邮箱、手机号、用户名格式验证
- ✅ **限流保护**：`checkRateLimit()` 防止暴力攻击
- ✅ **文件上传安全**：MIME 类型真实检测，大小限制
- ✅ **审计日志**：`auditLog()` 记录关键操作

### 安全函数示例

```php
// XSS 防护
$username = cleanInput($_POST['username']);

// 输入验证
if (!validateEmail($email)) {
    error('邮箱格式不正确');
}

// 限流（防暴力破解）
if (!checkRateLimit('login_' . $ip, 5, 300)) {
    error('登录尝试过多，请5分钟后再试');
}

// 审计日志
auditLog('创建用户', ['username' => $username]);
```

**详细文档**：查看 [SECURITY.md](docs/SECURITY.md)

---

## 💪 复杂业务支持

配置驱动 ≠ 功能受限！控制器层完全灵活，支持任何复杂业务。

### Medoo 支持的复杂查询

- ✅ **JOIN**（2-5 表关联）
- ✅ **子查询**
- ✅ **聚合函数**（COUNT、SUM、AVG）
- ✅ **事务处理**
- ✅ **批量操作**
- ✅ **原生 SQL**（极端情况）

### 示例：JOIN 查询（文章 + 作者 + 分类）

```php
$articles = $db->select('articles', [
    '[>]admin' => ['author_id' => 'id'],
    '[>]categories' => ['category_id' => 'id']
], [
    'articles.id',
    'articles.title',
    'admin.username(author_name)',
    'categories.name(category_name)'
], [
    'articles.status' => 1,
    'ORDER' => ['articles.id' => 'DESC']
]);
```

### 示例：事务处理（创建文章并扣积分）

```php
$db->action(function($db) {
    // 插入文章
    $db->insert('articles', [...]);
    $articleId = $db->id();
    
    // 扣除积分
    $db->update('users', ['points[-]' => 10], ['id' => $userId]);
    
    // 记录日志
    $db->insert('point_logs', [
        'user_id' => $userId,
        'amount' => -10,
        'ref_id' => $articleId
    ]);
});
```

**详细文档**：查看 [COMPLEX_QUERY.md](docs/COMPLEX_QUERY.md)

---

## 📚 完整文档

| 文档 | 说明 | 适用场景 |
|------|------|----------|
| 文档 | 说明 | 适用场景 |
|------|------|----------|
| [README.md](README.md) | **本文档** - 快速开始、基础使用 | 第一次使用 |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | **必读！** 架构原理、工作流程、文件关系 | 理解框架设计 |
| [EXAMPLES.md](docs/EXAMPLES.md) | **完整示例** - 浏览量统计、分页、API 等 | 学习开发 |
| [ADMIN_DEV.md](docs/ADMIN_DEV.md) | 后台开发指南、配置驱动开发 | 开发后台功能 |
| [PERMISSIONS.md](docs/PERMISSIONS.md) | **权限系统** - 权限配置、使用指南 | 配置权限 |
| [FIELD_TYPES.md](docs/FIELD_TYPES.md) | **字段类型完整参考** - 16+ 字段类型详解 | 查询字段类型 |
| [FIELD_CONFIG_REFERENCE.md](docs/FIELD_CONFIG_REFERENCE.md) | **字段配置快速参考** - 完整配置对照表 | 字段配置速查 |
| [SECURITY.md](docs/SECURITY.md) | 安全措施、最佳实践、生产环境清单 | 部署上线 |
| [COMPLEX_QUERY.md](docs/COMPLEX_QUERY.md) | 复杂查询示例（JOIN、事务、聚合等）| 处理复杂业务 |
| [QUICKSTART.md](docs/QUICKSTART.md) | 快速部署、常见问题 | 快速上手 |
| [DEPLOY.md](docs/DEPLOY.md) | 生产环境部署指南（单体部署） | 生产部署 |
| [SEPARATION_DEPLOY.md](docs/SEPARATION_DEPLOY.md) | **三端分离部署指南** - API/管理/客户端分离 | 分离部署 |
| [SERVER_CONFIG.md](docs/SERVER_CONFIG.md) | Nginx/Apache 伪静态配置 | 服务器配置 |
| [DATABASE_UPGRADE.md](docs/DATABASE_UPGRADE.md) | 数据库升级说明（v2.1.3）| 数据库升级 |
| [CHANGELOG.md](docs/CHANGELOG.md) | 版本更新日志 | 了解更新 |

### 推荐阅读顺序

1. **第一次使用**：README.md（本文）→ ARCHITECTURE.md
2. **开发后台功能**：ADMIN_DEV.md
3. **处理复杂业务**：COMPLEX_QUERY.md
4. **部署上线**：SECURITY.md → DEPLOY.md（单体）/ SEPARATION_DEPLOY.md（分离）

---

## 💡 核心概念

### 1. 配置驱动架构

```
用户点击菜单
    ↓
加载配置（CrudConfig.php）
    ↓
自动渲染 UI（crud-renderer.js）
    ↓
用户操作（新增/编辑/删除）
    ↓
调用 API（index.php 路由）
    ↓
执行业务逻辑（Controller）
    ↓
返回数据 / 成功失败
    ↓
前端刷新显示
```

### 2. 三层架构

| 层次 | 文件 | 作用 |
|------|------|------|
| **配置层** | `CrudConfig.php` | 定义 UI 如何显示 |
| **渲染层** | `crud-renderer.js` | 自动生成 HTML/JS |
| **业务层** | `XxxController.php` | 处理业务逻辑 |

**关键理解**：
- ✅ 配置只影响**前端 UI**
- ✅ 控制器可以写**任何复杂逻辑**
- ✅ 轻量级 ≠ 功能弱！

---

## 🎯 示例模块

框架自带 2 个完整示例模块：

### 1. 用户管理（CRUD 示例）

- **位置**：后台 → 用户管理 → 用户列表
- **功能**：完整的增删改查
- **文件**：
  - 配置：`app/config/CrudConfig.php::users()`
  - 控制器：`app/api/admin/UserController.php`
  - 路由：`public/index.php`（搜索 `/api/admin/users`）

### 2. 文章管理（高级示例）

- **位置**：后台 → 内容管理 → 文章管理
- **功能**：富文本、图片上传、日期选择、开关等
- **文件**：
  - 配置：`app/config/CrudConfig.php::articles()`
  - 控制器：`app/api/admin/ArticleController.php`
  - 路由：`public/index.php`（搜索 `/api/admin/articles`）

### 3. 前端 Views（可选示例）

- **位置**：访问首页 `/`
- **功能**：展示如何使用 Views 渲染 HTML 页面
- **文件**：
  - 控制器：`app/api/ArticleController.php`
  - 视图：`app/views/articles/`, `app/views/home/`
  - 路由：`public/index.php`（搜索 `Flight::render`）

---

## 🔧 常用辅助函数

框架提供了丰富的辅助函数（在 `app/helpers/functions.php` 和 `security.php`）：

### 数据库操作

```php
$db = db();                          // 获取 Medoo 实例
$users = $db->select('users', '*');  // 查询
```

### JSON 响应

```php
success($data, '操作成功');          // 成功响应
error('操作失败', 500);              // 错误响应
layuiTable($data, $count);          // Layui 表格专用
```

### 请求参数

```php
$page = getQuery('page', 1);        // GET 参数
$name = getPost('name');            // POST 参数
$token = getHeader('Authorization'); // 请求头
```

### 安全函数

```php
$clean = cleanInput($input);        // XSS 防护
validateEmail($email);              // 邮箱验证
validatePhone($phone);              // 手机号验证
checkRateLimit($key, 10, 60);       // 限流检查
auditLog('创建用户', [...]);        // 审计日志
```

### 用户信息

```php
$user = currentUser();              // 获取当前用户
```

### 密码加密

```php
$hash = hashPassword($password);    // 加密密码
verifyPassword($input, $hash);      // 验证密码
```

### 日志

```php
writeLog('消息', 'info');           // 写日志
```

---

## ⚙️ 常见问题

### 1. 接口返回 404

**原因**：伪静态未配置

**解决方案**：
1. 确认 Web 服务器根目录指向 `public/` 目录
2. Nginx：检查 `try_files` 配置
3. Apache：确认已启用 `mod_rewrite` 模块
4. phpstudy：确认站点已创建并指向 `public/` 目录

**详细配置**：查看 [SERVER_CONFIG.md](docs/SERVER_CONFIG.md)

---

### 2. 数据库连接失败

检查 `.env` 文件中的数据库配置是否正确。

---

### 3. Token 验证失败

检查请求头是否包含 `Authorization` 字段：

```javascript
headers: {
    'Authorization': 'Bearer your-token-here'
}
```

---

### 4. 文件上传失败

检查 `public/uploads/` 目录权限是否可写：

```bash
chmod 755 public/uploads  # Linux/Mac
```

Windows 下右键 → 属性 → 安全，添加写入权限。

---

### 5. 生产环境仍然显示错误详情

**原因**：调试模式没有关闭

**解决方案**：
1. 修改 `.env` 文件：`APP_DEBUG=false`
2. 重启 Web 服务器或清除 OPcache
3. 错误信息将记录到 `runtime/logs/` 目录

---

### 6. 后台字段类型不支持？

框架已支持 15+ 字段类型（富文本、上传、日期、开关等），查看 [ADMIN_DEV.md](docs/ADMIN_DEV.md)。

如需自定义字段类型，可修改 `public/admin/assets/js/crud-renderer.js`。


---

### 7. 如何处理复杂业务（JOIN、事务等）？

Medoo 完全支持！查看 [COMPLEX_QUERY.md](docs/COMPLEX_QUERY.md)。

---

## 🚀 开始你的项目

### 1. 复制框架

```bash
cp -r flight_base your_project_name
cd your_project_name
```

### 2. 修改配置

- 修改 `.env` 文件
- 修改 `example_db.sql` 创建你的数据表
- 删除示例模块（保留或修改 `articles`, `users`）

### 3. 开发新功能

1. 在 `app/config/CrudConfig.php` 添加配置
2. 在 `app/api/admin/` 创建控制器
3. 在 `public/index.php` 注册路由
4. 在 `app/config/CrudConfig.php` 的 getMenus() 里添加菜单

**详细步骤**：查看 [ADMIN_DEV.md](docs/ADMIN_DEV.md)

---

## 🎉 总结

### Flight Base 2.0 = 完美组合！

```
✅ 轻量级       - 核心代码 < 1000 行
✅ AI 友好      - 结构化配置，AI 轻松生成
✅ 配置驱动     - 零手写代码，节省 80%
✅ 安全完善     - 10+ 安全措施，生产可用
✅ 功能强大     - 支持任何复杂业务（JOIN、事务等）
✅ 易于扩展     - 5 分钟添加新模块
✅ 字段丰富     - 15+ 字段类型（富文本、上传、日期等）
✅ 界面美观     - 弹窗宽度 800px，专业美观
```

---

## License

MIT

## 支持

如有问题，请提交 Issue 或查看完整文档。

**开发愉快！** 🚀
