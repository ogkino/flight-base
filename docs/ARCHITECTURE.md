# Flight Base 2.0 架构说明

## 🎯 核心理念

**配置驱动 ≠ 不需要控制器！**

配置驱动只是让**前端自动渲染 UI**，真正的业务逻辑还是在**控制器**里处理。

---

## 🔄 完整工作流程（以文章管理为例）

```
┌─────────────────────────────────────────────────────────────┐
│  第一步：用户点击菜单 "文章管理"                              │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  第二步：前端加载配置                                         │
│  请求：GET /api/admin/config?page=articles                  │
│  ↓                                                           │
│  ConfigController::getPageConfig()                          │
│  ↓                                                           │
│  返回：CrudConfig::articles() 的配置                         │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  第三步：前端根据配置自动渲染 UI                              │
│  CrudRenderer 渲染器自动生成：                                │
│  ✅ 表格（字段、宽度、排序）                                  │
│  ✅ 搜索框（关键词搜索）                                      │
│  ✅ 工具栏按钮（新增）                                        │
│  ✅ 表单弹窗（标题、作者、内容）                              │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  第四步：用户操作触发 API 请求                                │
│  操作             →  API 请求          →  控制器方法          │
│  ────────────────────────────────────────────────────────   │
│  点击搜索/翻页    →  GET /api/admin/articles?page=1          │
│                   →  ArticleController::list()              │
│                                                              │
│  点击新增按钮     →  (前端弹窗，填写表单)                     │
│  提交表单        →  POST /api/admin/article                 │
│                   →  ArticleController::create()            │
│                                                              │
│  点击编辑按钮     →  (前端弹窗，回填数据)                     │
│  提交表单        →  POST /api/admin/article/123             │
│                   →  ArticleController::update(123)         │
│                                                              │
│  点击删除按钮     →  DELETE /api/admin/article/123           │
│                   →  ArticleController::delete(123)         │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  第五步：控制器处理业务逻辑                                   │
│  ArticleController::list()                                  │
│  {                                                           │
│      1. 验证权限（AuthMiddleware::checkAdmin()）             │
│      2. 接收参数（page, limit, keyword）                     │
│      3. 查询数据库（Medoo）                                  │
│      4. 返回 JSON（layuiTable()）                            │
│  }                                                           │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│  第六步：前端接收数据，自动渲染到表格                         │
│  Layui Table 自动刷新显示                                     │
└─────────────────────────────────────────────────────────────┘
```

---

## 📂 文件对应关系

### 以"文章管理"为例

| 文件 | 作用 | 代码示例 |
|------|------|----------|
| **`CrudConfig.php`**<br>`articles()` 配置 | **定义前端 UI 如何显示**<br>- 表格列<br>- 表单字段<br>- API 地址 | ```php<br>return [<br>  'table' => ['url' => '/api/admin/articles'],<br>  'form' => [...]<br>];``` |
| **`ArticleController.php`**<br>控制器 | **真正处理业务逻辑**<br>- list() 查询数据<br>- create() 创建<br>- update() 更新<br>- delete() 删除 | ```php<br>public static function list() {<br>  $articles = $db->select('articles', ...);<br>  layuiTable($articles, $count);<br>}``` |
| **`index.php`**<br>路由 | **连接 URL 和控制器**<br>定义哪个 URL 调用哪个方法 | ```php<br>Flight::route('GET /api/admin/articles', function(){<br>  ArticleController::list();<br>});``` |
| **`index.html`**<br>菜单 | **用户入口**<br>点击菜单加载配置 | ```html<br><dd><a data-page="articles">文章管理</a></dd>``` |
| **`crud-renderer.js`**<br>渲染器 | **自动生成 UI**<br>读取配置 → 渲染页面 | ```js<br>renderCrudPage(config, 'dynamic-content');``` |

---

## 🎨 工作流程图（简化版）

```
用户点击菜单
    ↓
加载配置（CrudConfig）
    ↓
渲染 UI（CrudRenderer）
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

---

## 💡 关键理解

### ❌ 误解：配置驱动就不需要控制器了

**错！** 配置只是让前端**不用手写 HTML/JS**，但业务逻辑依然需要控制器处理！

### ✅ 正确理解：配置驱动 = 前端自动化 + 后端依然需要

```
传统方式（1.0）：
  前端手写表格 + 手写表单 + 手写事件 = 500 行代码
  后端写控制器 = 100 行代码
  ────────────────────────────────────
  总计：600 行

配置驱动（2.0）：
  前端只需配置 = 50 行配置
  后端写控制器 = 100 行代码（不变）
  ────────────────────────────────────
  总计：150 行（减少 75%！）
```

**节省的是前端重复代码，不是后端业务逻辑！**

---

## 📝 UserController 和 AuthController 的关系

### UserController（用户管理）

```php
// app/api/admin/UserController.php
class UserController
{
    public static function list() { /* 用户列表 */ }
    public static function create() { /* 创建用户 */ }
    public static function update($id) { /* 更新用户 */ }
    public static function delete($id) { /* 删除用户 */ }
}
```

**作用**：管理后台的"用户列表"功能，CRUD 操作

### AuthController（认证管理）

```php
// app/api/admin/AuthController.php
class AuthController
{
    public static function login() { /* 管理员登录 */ }
    public static function logout() { /* 退出登录 */ }
    public static function info() { /* 获取当前管理员信息 */ }
    public static function changePassword() { /* 修改密码 */ }
}
```

**作用**：管理员的登录、退出、修改密码等**账号相关**功能

### 关系对比

| 控制器 | 管理对象 | 主要功能 |
|--------|----------|----------|
| **UserController** | **系统用户**<br>（og_users 表） | 在后台管理普通用户<br>- 查看用户列表<br>- 新增/编辑/删除用户 |
| **AuthController** | **管理员自己**<br>（og_admin 表） | 管理员账号操作<br>- 登录后台<br>- 修改自己的密码<br>- 查看自己的信息 |

**举例**：
- 管理员登录后台 → `AuthController::login()`
- 管理员查看普通用户列表 → `UserController::list()`
- 管理员修改自己的密码 → `AuthController::changePassword()`
- 管理员编辑普通用户信息 → `UserController::update()`

---

## 🔥 配置驱动的优势

### 传统方式：每个模块都要手写

```javascript
// users.js（500 行）
table.render({ ... });
layer.open({ content: `<form>...</form>` });
function addUser() { ... }
function editUser() { ... }
...

// articles.js（500 行，几乎一样！）
table.render({ ... });
layer.open({ content: `<form>...</form>` });
function addArticle() { ... }
function editArticle() { ... }
...
```

**问题**：大量重复代码，改一个 bug 要改 N 个文件！

### 配置驱动：一套渲染器 + N 个配置

```php
// CrudConfig.php
public static function users() { return [ /* 配置 */ ]; }
public static function articles() { return [ /* 配置 */ ]; }
public static function products() { return [ /* 配置 */ ]; }
```

```javascript
// crud-renderer.js（300 行，通用！）
class CrudRenderer {
    render() { /* 根据配置自动生成 */ }
}
```

**优势**：
- ✅ 一处修改，全局生效
- ✅ AI 可以轻松生成配置
- ✅ 代码量减少 75%
- ✅ 维护成本极低

---

## 🚀 添加新模块的完整步骤

### 示例：添加"商品管理"

#### 1️⃣ 创建数据表

```sql
CREATE TABLE `og_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2️⃣ 创建控制器 `app/api/admin/ProductController.php`

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class ProductController
{
    public static function list() { /* 查询商品 */ }
    public static function create() { /* 创建商品 */ }
    public static function update($id) { /* 更新商品 */ }
    public static function delete($id) { /* 删除商品 */ }
}
```

#### 3️⃣ 注册路由 `public/index.php`

```php
Flight::route('GET /api/admin/products', function(){
    \App\Api\Admin\ProductController::list();
});
// ... 其他路由
```

#### 4️⃣ 添加配置 `app/config/CrudConfig.php`

```php
public static function products()
{
    return [
        'page' => ['title' => '商品管理'],
        'table' => [ /* 表格配置 */ ],
        'form' => [ /* 表单配置 */ ],
        'api' => [ /* API 地址 */ ]
    ];
}
```

#### 5️⃣ 在 `app/config/CrudConfig.php` 的 getMenus() 里添加菜单

#### ✅ 完成！

刷新页面，点击"商品列表"，自动生成完整的 CRUD 页面！

---

## 📚 总结

### 配置驱动架构 = 3 层分离

```
┌─────────────────────┐
│   前端配置层         │  ← CrudConfig.php（定义 UI）
│   (CrudConfig)      │
└─────────────────────┘
          ↓
┌─────────────────────┐
│   自动渲染层         │  ← crud-renderer.js（生成 HTML/JS）
│   (CrudRenderer)    │
└─────────────────────┘
          ↓
┌─────────────────────┐
│   业务逻辑层         │  ← XxxController.php（处理数据）
│   (Controller)      │
└─────────────────────┘
          ↓
┌─────────────────────┐
│   数据库层          │  ← Medoo（ORM）
│   (Database)        │
└─────────────────────┘
```

### 核心优势

- ✅ **前端零手写**：配置即 UI
- ✅ **后端标准化**：4 个方法搞定 CRUD
- ✅ **AI 友好**：结构化配置，AI 轻松生成
- ✅ **维护简单**：修改配置即可，不碰渲染器

---

**现在明白了吗？有任何疑问随时问我！**
