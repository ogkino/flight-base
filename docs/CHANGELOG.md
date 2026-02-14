# 更新日志

## [2.3.7] - 2026-02-12 🐛 修复 CRUD 设计器显示与自动识别问题

### 修复
- ✅ **修复列表项标题显示**：在 CRUD 设计器中，优先使用 `label` 作为列表项标题，解决了 Switch 等组件显示 "开启|关闭" 而非字段名的问题。
- ✅ **修复时间戳自动识别**：修复了 `timestamp` 类型的字段在自动识别模式下被误判为文本的问题，现在会正确映射为 `datetime` 类型。

### 优化
- ⚡️ **优化新建列默认值**：在 CRUD 设计器中添加表格列时，`type` 字段默认选中 "自动识别" (Auto)，提高了配置效率。

## [2.3.6] - 2026-01-23 🧹 清理调试代码 & 稳定版发布

### 优化
- 🧹 **移除调试日志**：清理了 `crud-renderer.js` 和 `AdminManageController.php` 中的 `console.log` 和 `writeLog`。
- 📦 **版本号更新**：更新前端资源版本号至 2.3.6，确保浏览器加载最新代码。

---

## [2.3.5] - 2026-01-23 🐛 修复权限多选只保存最后一个的问题

### 修复
- ✅ **重构权限选择逻辑**：弃用依赖 DOM 查询 (`querySelectorAll`) 的方式，改为使用 State (内存对象) 维护权限状态。
- ✅ **解决事件冲突**：为每个权限渲染实例生成唯一的 `lay-filter` ID，防止多次渲染导致的事件监听冲突。

### 问题原因
之前的实现依赖 `container.querySelectorAll('input:checked')` 来计算选中的权限。但在 Layui 的事件回调中，DOM 状态可能尚未完全同步，或者由于多次渲染导致 `container` 引用失效，导致每次点击时只能检测到当前点击的那个复选框，从而丢失了之前选中的项。

新的实现方式维护一个 `currentPermissions` 对象，每次点击复选框时，直接更新这个对象的对应状态，不再依赖 DOM 查询，彻底解决了"点一个丢一个"的问题。

### 修改文件
- `public/admin/assets/js/crud-renderer.js` - 重构 `renderPermissions`
- `public/admin/index.html` - 更新版本号至 2.3.5

---

## [2.3.4] - 2026-01-23 🐛 修复后端 JSON 解析问题

---

## [2.3.3] - 2026-01-23 🐛 修复前端提交数据缺失

### 问题原因

**问题 1：权限配置保存为空 `{}`**

原因：Layui 渲染复选框后，原生 `addEventListener` 失效
```javascript
// ❌ 错误：使用原生事件监听
checkbox.addEventListener('change', function() {
    updatePermissionsValue();
});

// ✅ 修复：使用 Layui 的 form.on
form.on('checkbox(permission-change)', function(data){
    updatePermissionsValue();
});
```

**问题 2：状态开关无法更新**

原因：两个同名 input 导致提交冲突
```javascript
// ❌ 错误：两个同名字段
<input type="hidden" name="status" value="0">
<input type="checkbox" name="status" value="1">

// ✅ 修复：分离命名，switch 监听同步到 hidden
<input type="checkbox" name="status_switch" lay-filter="switch-status">
<input type="hidden" name="status" id="field_status_value" value="0">

// JS: 监听 switch 变化并同步
form.on('switch(switch-status)', function(data){
    valueInput.value = data.elem.checked ? '1' : '0';
});
```

### 调试方法

打开浏览器开发者工具（F12）→ Console，提交表单时会看到：

```
注册 switch 监听: switch-status
复选框状态改变: articles list true
复选框状态改变: articles create true
权限已更新: {articles: ['list', 'create']} JSON: {"articles":["list","create"]}
Switch 状态改变: status = 1
```

### 修改文件
- `public/admin/assets/js/crud-renderer.js` - 修复权限和 switch 监听
- `public/admin/index.html` - 更新版本号至 2.3.2
- `app/api/admin/AdminManageController.php` - 清理调试日志

---

## [2.3.1] - 2026-01-23 🐛 修复权限和状态无法保存（初次尝试，未完全解决）

### 修复
- ⚠️ **修复 switch 组件值不提交**：为 switch 添加隐藏字段（后发现方案有问题）
- ✅ **修复 status 硬编码**：create 方法中 status 从表单获取
- ✅ **添加调试日志**：记录 POST 数据

### 问题原因

**问题 1：Switch 组件值不提交**

Layui 的 switch 基于 checkbox：
- ✅ 选中时：提交 `value` 值（1）
- ❌ 未选中时：**不提交任何值**，导致 `isset($_POST['status'])` 返回 false

**解决方案**：
```javascript
// 添加隐藏字段，确保值始终被提交
html += `<input type="hidden" name="${field.name}" value="0">
         <input type="checkbox" name="${field.name}" value="1" ...>`;
```

**效果**：
- 未选中：提交 `status=0`
- 选中：提交 `status=0` 和 `status=1`，后者覆盖前者，最终为 `status=1`

**问题 2：Status 硬编码**

```php
// ❌ 错误：硬编码为 1
'status' => 1,

// ✅ 修复：从表单获取
$status = (int)getPost('status', 1);
'status' => $status,
```

### 调试步骤

如果权限和状态仍然无法保存，请按以下步骤排查：

#### 步骤 1：查看日志

```bash
# 查看日志文件
tail -f runtime/logs/info.log
```

日志内容示例：
```
[2026-01-23 15:30:00] [info] 创建管理员 - 收到的 POST 数据: {"username":"test2","password":"password","nickname":"测试2","permissions":"{\"users\":[\"list\",\"create\"]}","status":"1"}
[2026-01-23 15:30:00] [info] 创建管理员 - 解析数据: permissions=[{"users":["list","create"]}], status=[1]
[2026-01-23 15:30:00] [info] 创建管理员 - 要插入的数据: {"username":"test2","password":"***","nickname":"测试2","permissions":"{\"users\":[\"list\",\"create\"]}","status":1}
```

#### 步骤 2：检查数据库

```sql
-- 查看管理员表数据
SELECT id, username, nickname, permissions, status FROM og_admin;
```

#### 步骤 3：前端调试

打开浏览器开发者工具（F12）→ Network → 提交表单 → 查看请求：

**Request Payload 应该包含**：
```json
{
  "username": "test2",
  "password": "password",
  "nickname": "测试2",
  "permissions": "{\"users\":[\"list\",\"create\"]}",
  "status": "1"
}
```

### 修改文件
- `app/api/admin/AdminManageController.php` - 修复 status 获取，添加调试日志
- `public/admin/assets/js/crud-renderer.js` - 修复 switch 值提交
- `public/admin/index.html` - 更新版本号至 2.3.1

---

## [2.3.0] - 2026-01-23 ✨ 统一字段配置格式 & 增强字段控制

### 修复
- ✅ **修复权限无法保存**：初始化时调用 `updatePermissionsValue()` 确保权限值正确设置

### 新增
- ✅ **required_on_edit**：只在编辑时必填（典型场景：审核拒绝原因）
- ✅ **hidden_on_add**：新增时隐藏字段（典型场景：最后修改时间）
- ✅ **disabled_on_add**：新增时禁用字段
- ✅ **向后兼容**：支持 `required: 'edit'` 旧格式

### 优化
- ✅ **统一配置格式**：所有字段控制属性统一为 `xxx_on_add` / `xxx_on_edit` 格式
- ✅ **更明确的命名**：
  - `required_on_add` - 只在新增时必填
  - `required_on_edit` - 只在编辑时必填
  - `required_on_both` - 新增和编辑都必填
  - `hidden_on_add` - 新增时隐藏
  - `hidden_on_edit` - 编辑时隐藏
  - `disabled_on_add` - 新增时禁用
  - `disabled_on_edit` - 编辑时禁用

### 配置格式对比

**旧格式（仍然支持）**：
```php
'required' => true          // 始终必填
'required' => 'add'         // 只在新增时必填
'required' => 'edit'        // 只在编辑时必填
'hidden_on_edit' => true    // 编辑时隐藏
'disabled_on_edit' => true  // 编辑时禁用
```

**新格式（推荐，更统一）**：
```php
'required_on_both' => true  // 始终必填
'required_on_add' => true   // 只在新增时必填
'required_on_edit' => true  // 只在编辑时必填
'hidden_on_add' => true     // 新增时隐藏
'hidden_on_edit' => true    // 编辑时隐藏
'disabled_on_add' => true   // 新增时禁用
'disabled_on_edit' => true  // 编辑时禁用
```

### 典型使用场景

#### 场景 1：审核拒绝原因

```php
[
    'name' => 'reject_reason',
    'label' => '拒绝原因',
    'required_on_edit' => true,  // 编辑时必填
    'hidden_on_add' => true      // 新增时隐藏
]
```

**效果**：
- ❌ 新增时：不显示此字段
- ✅ 编辑时：显示且必填

#### 场景 2：最后修改信息

```php
[
    'name' => 'last_modified_by',
    'label' => '最后修改人',
    'hidden_on_add' => true,     // 新增时隐藏
    'disabled_on_edit' => true   // 编辑时禁用（只读）
]
```

**效果**：
- ❌ 新增时：不显示
- ✅ 编辑时：显示但不可修改

#### 场景 3：邀请码

```php
[
    'name' => 'invite_code',
    'label' => '邀请码',
    'required_on_add' => true,   // 新增时必填
    'hidden_on_edit' => true     // 编辑时隐藏
]
```

**效果**：
- ✅ 新增时：显示且必填
- ❌ 编辑时：不显示

### 文档更新
- ✅ `docs/ADMIN_DEV.md` - 新增完整的字段控制规则说明
- ✅ 新增工单管理完整示例（展示所有规则的实际应用）

### 修改文件
- `public/admin/assets/js/crud-renderer.js` - 实现新字段控制逻辑
- `docs/ADMIN_DEV.md` - 完整的字段配置文档（400+ 行）
- `docs/CHANGELOG.md` - 记录 v2.3.0
- `public/admin/index.html` - 更新版本号至 2.3.0

---

## [2.2.0 hotfix4] - 2026-01-23 🐛 修复更新管理员 & 统一配置规范

### 修复
- ✅ 修复更新管理员数据不生效的问题（字段获取逻辑错误）
- ✅ 修复 `disabled_on_edit` 属性无效（未实现禁用功能）
- ✅ 修复用户名和权限无法保存的问题

### 优化
- ✅ **统一字段配置规范**：所有模块统一使用 `required: 'add'` 表示只在新增时必填
- ✅ **完善字段属性支持**：
  - `disabled_on_edit` - 编辑时禁用字段（显示但不可修改）
  - `hidden_on_edit` - 编辑时隐藏字段
  - `hidden_on_super_admin` - 超级管理员编辑时隐藏
- ✅ **完善文档**：在 ADMIN_DEV.md 中新增"字段配置属性完整参考"和"特殊页面配置"章节

### 字段配置规范

**旧写法（不统一）**：
```php
// users 模块
'required' => 'add'

// admins 模块
'required' => true,
'required_on_edit' => false
```

**新写法（统一）**：
```php
// 所有模块统一
'required' => 'add'  // 只在新增时必填
'required' => true   // 始终必填
'required' => false  // 非必填
```

### 字段禁用和隐藏

**禁用字段（编辑时显示但不可修改）**：
```php
[
    'name' => 'username',
    'disabled_on_edit' => true  // 编辑时禁用
]
```

**隐藏字段（编辑时完全不显示）**：
```php
[
    'name' => 'invite_code',
    'hidden_on_edit' => true  // 编辑时隐藏
]
```

### 修改文件
- `app/api/admin/AdminManageController.php` - 修复 update 方法逻辑
- `app/config/CrudConfig.php` - 统一配置规范
- `public/admin/assets/js/crud-renderer.js` - 实现字段禁用和隐藏功能
- `docs/ADMIN_DEV.md` - 新增详细配置说明
- `public/admin/index.html` - 更新版本号至 2.2.3

---

## [2.2.0 hotfix3] - 2026-01-23 ✨ 优化权限体验

### 新增
- ✅ **权限菜单过滤**：根据管理员权限自动隐藏无权限的菜单项
- ✅ 编辑管理员时密码非必填（留空表示不修改）

### 优化
- ✅ 超级管理员（ID=1）显示所有菜单
- ✅ 普通管理员只显示有权限的菜单
- ✅ 特殊页面（仪表盘、修改密码）始终显示
- ✅ 添加密码字段提示："编辑时留空表示不修改密码"

### 修改文件
- `app/api/admin/ConfigController.php` - 实现菜单权限过滤
- `public/admin/assets/js/crud-renderer.js` - 支持 `required_on_edit` 属性
- `app/config/CrudConfig.php` - 添加密码字段提示
- `public/admin/index.html` & `login.html` - 更新版本号至 2.2.2

---

## [2.2.0 hotfix2] - 2026-01-23 🐛 修复权限配置加载错误

### 修复
- ✅ 修复 `crud-renderer.js` 中 `config is not defined` 错误
- ✅ 将 `config.api.permissions` 改为 `this.config.api.permissions`

### 修改文件
- `public/admin/assets/js/crud-renderer.js` - 修复变量作用域问题
- `public/admin/index.html` - 更新版本号至 2.2.1

---

## [2.2.0 hotfix] - 2026-01-23 🐛 修复管理员管理问题

### 修复
- ✅ 修复 DashboardController 语法错误（导致500错误）
- ✅ 修复管理员管理缺少"新增"按钮
- ✅ 修复管理员编辑/删除接口 URL 缺少 {id} 占位符（导致405错误）
- ✅ 修复权限配置无法加载的问题

### 修改文件
- `app/api/admin/DashboardController.php` - 移除错误的 Medoo 语法
- `app/config/CrudConfig.php` - 添加工具栏按钮，修复 API URL

---

## [2.2.0] - 2026-01-23 🔐 权限系统

### 新增
- ✅ **管理员 CRUD**：完整的管理员管理功能（列表、新增、编辑、删除）
- ✅ **权限系统**：基于 JSON 的轻量级权限配置系统
- ✅ **超级管理员保护**：ID=1 的管理员禁止删除，拥有所有权限
- ✅ **权限验证**：所有控制器自动验证权限（`checkPermission()`）
- ✅ **权限配置界面**：可视化的权限配置表单（多选框）
- ✅ **权限辅助函数**：`hasPermission()`、`getAdminPermissions()`、`getAllPermissions()`

### 数据库变更
- ✅ `og_admin` 表新增 `permissions` 字段（TEXT，JSON 格式）
- ✅ 插入测试管理员（用户名：test，密码：password）

### 权限格式

**存储格式**（JSON）：
```json
{
    "users": ["list", "create", "update", "delete"],
    "articles": ["list", "create", "update"]
}
```

**说明**：
- 模块名：对应配置方法名（如 `users`、`articles`）
- 操作名：`list`（查看）、`create`（新增）、`update`（编辑）、`delete`（删除）
- ID=1 的超级管理员 `permissions` 为 `NULL`，表示拥有所有权限

### 权限验证示例

**控制器中验证**：

```php
public static function list()
{
    AuthMiddleware::checkAdmin();
    checkPermission('users', 'list');  // 验证权限
    
    // 业务逻辑...
}
```

**返回结果**：
- ✅ 有权限：正常执行
- ❌ 无权限：返回 `{"code": 403, "msg": "无权限访问"}`

### 新增文件

| 文件 | 说明 |
|------|------|
| `app/helpers/permission.php` | 权限验证辅助函数 |
| `app/api/admin/AdminManageController.php` | 管理员管理控制器 |

### 修改文件

| 文件 | 修改内容 |
|------|---------|
| `database.sql` | admin 表新增 permissions 字段，插入测试管理员 |
| `app/config/CrudConfig.php` | 新增 `admins()` 配置，菜单中添加"管理员管理" |
| `app/middleware/AuthMiddleware.php` | 保存 `admin_id` 用于权限验证 |
| `app/api/admin/UserController.php` | 所有方法添加权限验证 |
| `app/api/admin/ArticleController.php` | 所有方法添加权限验证 |
| `public/index.php` | 加载 permission.php，新增管理员管理路由 |
| `public/admin/assets/js/crud-renderer.js` | 支持 `permissions` 字段类型 |
| `composer.json` | 自动加载 permission.php |

### 使用说明

1. **重新导入数据库**：`database.sql`（包含权限字段）
2. **测试账号**：
   - 超级管理员：`admin` / `password`（所有权限）
   - 测试管理员：`test` / `password`（部分权限）
3. **配置权限**：后台 → 系统管理 → 管理员管理 → 编辑 → 权限配置

---

## [2.1.6 hotfix2] - 2026-01-23 🎨 菜单渲染优化

### 修复
- ✅ **菜单渲染统一**：所有只有 1 个子项的分组都不显示下拉，直接显示为一级菜单
- ✅ **父级图标配置**：支持分组级别的图标配置，不再强制使用第一个子项的图标

### 新增
- ✅ **新菜单配置格式**：支持 `icon` + `items` 的配置方式
- ✅ **向后兼容**：旧的配置格式仍然支持

### 优化
- ✅ 菜单渲染逻辑优化，更清晰易懂
- ✅ 删除测试文件 `test_config.php`

### 菜单配置示例

**新格式（推荐）**：

```php
'内容管理' => [
    'icon' => 'layui-icon-template-1',  // 父级图标
    'items' => [
        ['name' => '文章管理', 'page' => 'articles', 'icon' => 'layui-icon-file']
    ]
]
```

**渲染规则**：
- 1 个子项 → 一级菜单（无下拉）
- 2+ 个子项 → 分组菜单（有下拉）

---

## [2.1.6 hotfix] - 2026-01-23 🐛 修复菜单和配置问题

### 修复
- ✅ **配置加载优化**：直接重新加载配置文件，避免缓存问题
- ✅ **调试支持**：添加日志记录，便于排查配置加载问题

### 新增
- ✅ **配置测试工具**：`public/test_config.php`，用于测试配置是否正确加载

### 优化
- ✅ 更新版本号到 2.1.6，清除浏览器缓存
- ✅ 添加控制台日志，便于调试系统名称加载

### 测试步骤

1. **清除 PHP 缓存**（如果使用了 opcache）：
   - 重启 Apache/Nginx/PHP-FPM
   - 或访问 phpinfo() 查看 opcache 配置

2. **测试配置加载**：
   - 访问 `http://your-domain.com/test_config.php`
   - 检查 `ADMIN_NAME` 和 `config('app')['admin_name']` 是否正确
   - 测试完成后删除此文件

3. **强制刷新浏览器**：
   - 按 `Ctrl + Shift + R`（Windows/Linux）
   - 或 `Cmd + Shift + R`（Mac）

4. **检查控制台**：
   - 打开浏览器开发者工具 → Console
   - 查看 "系统名称已更新为：xxx" 日志

---

## [2.1.6] - 2026-01-23 🚀 自动化菜单 + 真实仪表盘

### 新增
- ✅ **系统名称配置**：可在 `app.php` 中配置后台管理系统名称
- ✅ **自动菜单生成**：菜单自动从 `CrudConfig::getMenus()` 读取，无需手动修改 HTML
- ✅ **真实仪表盘**：显示真实的统计数据（用户数、文章数、浏览量）
- ✅ **仪表盘控制器**：`DashboardController` 提供统计接口
- ✅ **菜单配置化**：在 `CrudConfig` 中配置菜单分组和层级

### 优化
- ✅ **前端自动化**：系统名称、菜单、统计数据全部从后端加载
- ✅ **配置驱动扩展**：新增页面自动出现在菜单中，无需修改前端代码
- ✅ **统计接口完善**：独立的统计接口，可扩展更多统计维度

### 新增接口

| 接口 | 说明 |
|------|------|
| `GET /api/admin/system` | 获取系统配置（名称、版本） |
| `GET /api/admin/menus` | 获取菜单配置 |
| `GET /api/admin/stats/users` | 用户总数统计 |
| `GET /api/admin/stats/articles` | 文章总数统计 |
| `GET /api/admin/stats/views` | 浏览量统计 |
| `GET /api/admin/stats/system` | 系统状态 |

### 使用说明

**1. 配置系统名称**

编辑 `app/config/app.php`：

```php
'admin_name' => env('ADMIN_NAME', 'My Admin'),
```

或在 `.env` 中：

```
ADMIN_NAME=我的管理系统
```

**2. 配置菜单**

编辑 `app/config/CrudConfig.php` 中的 `getMenus()` 方法：

```php
public static function getMenus()
{
    return [
        '仪表盘' => [
            ['name' => '数据统计', 'page' => 'dashboard', 'icon' => 'layui-icon-chart']
        ],
        '内容管理' => [
            ['name' => '文章管理', 'page' => 'articles', 'icon' => 'layui-icon-file'],
            ['name' => '分类管理', 'page' => 'categories', 'icon' => 'layui-icon-template']
        ]
    ];
}
```

**3. 添加新页面**

只需在 `CrudConfig` 中添加配置方法即可：

```php
// 1. 在 getMenus() 中添加菜单项
['name' => '商品管理', 'page' => 'products', 'icon' => 'layui-icon-cart']

// 2. 添加配置方法
public static function products() {
    return [ /* 配置 */ ];
}
```

前端会自动显示菜单，无需修改 HTML！

---

## [2.1.5] - 2026-01-23 🎨 前端优化 + 完整示例

### 新增
- ✅ **浏览量统计功能**：文章详情页自动增加浏览量（使用原子操作）
- ✅ **开发示例文档**：新增 `docs/EXAMPLES.md`，包含 6 个完整示例

### 优化
- ✅ **前端文章列表页面**：支持封面图显示、富文本摘要提取、优化布局样式
- ✅ **前端文章详情页面**：支持封面图显示、完整富文本内容渲染、富文本样式优化
- ✅ **文档目录整理**：所有文档移动到 `docs/` 目录，清理无用文档
- ✅ **代码注释**：增加详细的功能说明注释

### 修复
- ✅ 修复前端文章页面无法显示封面图的问题
- ✅ 修复富文本内容被转义为纯文本的问题
- ✅ 修复浏览量不增加的问题

### 示例文档内容

**EXAMPLES.md** 包含以下完整示例：

1. **文章浏览量统计** - 原子操作、视图渲染、404 处理
2. **带分页的文章列表** - 分页计算、模糊查询、排序
3. **API 接口** - JSON 响应、错误处理、路由定义
4. **表单提交处理** - 数据验证、清洗、入库
5. **更新数据** - 部分字段更新、时间戳
6. **删除数据** - 软删除 vs 硬删除、批量删除

### 目录结构变化

**调整前**：
```
flight-base/
├── README.md
├── CHANGELOG.md
├── QUICKSTART.md
├── DEPLOY.md
├── ADMIN_DEV.md
├── ... (其他 11 个 .md 文件)
```

**调整后**：
```
flight-base/
├── README.md          # 主文档（保留在根目录）
├── docs/              # 📖 文档目录
│   ├── README.md          # 文档索引（新增）
│   ├── QUICKSTART.md
│   ├── ARCHITECTURE.md
│   ├── ADMIN_DEV.md
│   ├── FIELD_TYPES.md
│   ├── SECURITY.md
│   ├── COMPLEX_QUERY.md
│   ├── DEPLOY.md
│   ├── SERVER_CONFIG.md
│   ├── DATABASE_UPGRADE.md
│   ├── BUGFIX_2.1.4.md
│   └── CHANGELOG.md
```

### 文档引用规则

- **从根目录引用文档**：使用 `docs/XXX.md`
- **文档间互相引用**：直接使用 `XXX.md`（同目录）
- **从文档引用根目录**：使用 `../README.md`

---

## [2.1.4] - 2026-01-23 🐛 修复字段名称问题

### 修复
- ✅ **前端文章控制器**：`status` 字段改为 `is_published`（与数据库保持一致）
- ✅ **用户编辑路由**：HTTP 方法从 `PUT` 改为 `POST`（与文章管理保持一致）

### 影响范围
- 修复前端文章列表页面 500 错误（`/articles`）
- 修复前端文章详情页面 500 错误（`/article/:id`）
- 修复后台用户编辑功能（"Method Not Allowed" 错误）

---

## [2.1.3] - 2026-01-23 📊 数据库字段扩展

### 新增
- ✅ **文章表字段扩展**：新增 `cover`（封面图）、`publish_date`（发布日期）、`is_published`（是否发布）
- ✅ **控制器更新**：ArticleController 支持新字段的增删改查
- ✅ **测试数据优化**：6 篇示例文章，包含富文本内容

### 数据库变更
- `og_articles` 表新增字段：
  - `cover` VARCHAR(255) - 封面图路径
  - `publish_date` DATE - 发布日期
  - `is_published` TINYINT(1) - 是否发布（1发布 0草稿）
- 重命名字段：`status` → `is_published`（语义更清晰）
- 示例数据：使用富文本 HTML 格式

### 升级说明
**重要**：需要重新导入 `database.sql` 文件，旧数据会被清空！

---

## [2.1.2] - 2026-01-23 🐛 修复提交 bug

### 修复
- ✅ **修复 API 配置错误**：移除 URL 中的 HTTP 方法（GET/POST/DELETE）
- ✅ **修复 post%20 错误**：API 配置不应包含 HTTP 方法，应在 request 调用时通过 method 参数指定

### 说明
- **错误原因**：API 配置中写了 `'add' => 'POST /api/admin/article'`，导致 URL 变成 `/post%20/api/admin/article`
- **正确写法**：`'add' => '/api/admin/article'`，HTTP 方法在 `request(url, {method: 'POST'})` 中指定

### 清理
- ✅ 删除测试文件：`test-editor.html`, `TEST_EDITOR.md`

---

## [2.1.1] - 2026-01-23 🎨 富文本编辑器修复

### 修复
- ✅ **富文本编辑器正常工作**：集成 wangEditor 替换简单的 contenteditable
- ✅ **图片上传支持**：富文本编辑器内可直接上传图片
- ✅ **编辑器销毁**：弹窗关闭时自动销毁编辑器实例，避免内存泄漏

### 优化
- ✅ 工具栏包含完整功能：标题、加粗、斜体、颜色、列表、表格、全屏等
- ✅ 自动保存内容到隐藏 textarea，提交表单时自动获取
- ✅ 支持配置占位符、上传接口等

### 依赖
- 新增：wangEditor (通过 CDN 引入，无需安装)

---

## [2.1.0] - 2026-01-23 🎨 增强版

### 新增
- ✅ **15+ 字段类型支持**：富文本、上传、日期、开关、滑块、颜色选择器等
- ✅ **文件上传功能**：`UploadController` 自动处理图片上传
- ✅ **动态弹窗宽度**：根据字段类型自动调整（富文本 90%，普通 800px）
- ✅ **字段提示信息**：支持 `tip` 参数显示字段说明

### 优化
- ✅ **CrudConfig.php 移至 config 目录**：更符合配置文件命名规范
- ✅ **弹窗宽度优化**：从 600px 增加到 800px，更美观专业
- ✅ **README.md 全面重写**：目录结构、字段类型、示例模块、文档索引

### 字段类型列表
- input, password, textarea, editor（富文本）
- number, radio, select, switch（开关）
- date, datetime, time（日期时间）
- upload, image（文件/图片上传）
- color（颜色选择器）, slider（滑块）

### 文件变动
- 移动：`app/helpers/CrudConfig.php` → `app/config/CrudConfig.php`
- 新增：`app/api/admin/UploadController.php`（文件上传）
- 更新：`public/admin/assets/js/crud-renderer.js`（v2.1，550 行 → 700 行）
- 更新：`README.md`（全面重写，400 行 → 700 行）

---

## [2.0.0] - 2026-01-23 🎉 重大升级

### 🚀 配置驱动架构

**核心理念**：后端配置 → 前端自动渲染 → 零手写代码

### 新增
- ✅ **CrudConfig 配置类**：定义页面配置
- ✅ **CrudRenderer 渲染器**：自动生成表格、表单、搜索
- ✅ **ConfigController**：配置接口
- ✅ **全新 Admin 界面**：基于配置驱动的极简后台

### 核心文件
- `app/config/CrudConfig.php` - 配置定义（已移至 config 目录）
- `public/admin/assets/js/crud-renderer.js` - 通用渲染器  
- `app/api/admin/ConfigController.php` - 配置接口
- `public/admin/index.html` - 全新后台（200 行）

### 优势
- ✅ 零手写代码：只需配置
- ✅ AI 极度友好：结构化配置，AI 轻松生成
- ✅ 超级轻量：核心代码 < 500 行
- ✅ 高度复用：一套渲染器通用所有 CRUD

### 删除
- ❌ 删除手写的复杂表格/表单代码（500+ 行）
- ❌ 删除 layout 组件（不再需要）

### 文档
- ✅ 全新 `ADMIN_DEV.md` - 配置驱动开发指南
- ✅ 包含完整示例和 AI 提示词

---

## [1.0.5] - 2026-01-23

### 重大重构 🎉
- ✅ **统一后台架构为单页应用（SPA）**
  - 删除 `change-password.html`，整合到 `index.html`
  - 建立标准化的页面路由机制
  - 统一的内容区切换函数 `showPage()`

### 新增
- ✅ **创建 ADMIN_DEV.md 开发文档** ⭐
  - 详细的后台扩展开发指南
  - 包含如何添加菜单、页面、接口的完整示例
  - 涵盖命名规范、代码组织、最佳实践
- ✅ 新增统一的页面路由配置 `pageRoutes`
- ✅ 新增页面切换函数：`showDashboard()`, `showUserList()`, `showPasswordPage()`

### 优化
- ✅ 修改密码功能整合到主页面，无需跳转
- ✅ 优化表格宽度，占满整个内容区
- ✅ CSS 调整：`.admin-content` 宽度计算，表格容器优化
- ✅ 建立可扩展的规范，适应从简单到复杂的系统演进

### 修复
- ✅ 修复表格右侧空白过大问题
- ✅ 修复页面切换时的状态保持问题
- ✅ 修复 `loadAdminInfo()` 函数调用问题

### 文档
- ✅ 新增 `ADMIN_DEV.md` - 后台开发完整指南
- ✅ 明确单页应用架构设计理念
- ✅ 提供可复用的代码模板和示例

---

## [1.0.4] - 2026-01-23

### 新增
- ✅ 添加修改密码功能（`/admin/change-password.html`）
- ✅ 新增修改密码 API（`POST /api/admin/change-password`）
- ✅ 在系统管理菜单中增加"修改密码"入口
- ✅ **新增 `layuiTable()` 辅助函数**：专门用于 Layui 表格数据返回
- ✅ **新增 `loadUserInfo()` 函数**：加载用户信息到顶部导航栏

### 优化
- ✅ 优化侧边栏样式，改为深色主题，与顶部导航栏统一
- ✅ 优化菜单激活状态样式
- ✅ 统一所有后台页面的菜单布局
- ✅ 简化 `UserController::list()` 代码，使用 `layuiTable()` 函数
- ✅ **优化后台布局**：侧边栏宽度从 220px 调整为 200px，内容区更宽敞
- ✅ **优化表格布局**：工具栏和表格合并在一个容器内，视觉更统一
- ✅ **优化按钮尺寸**：搜索栏按钮改为小尺寸（`layui-btn-sm`）

### 修复
- 🐛 **修复 `getQuery()` 函数变量名错误**：`$query_key` → `$key`
- 🐛 **修复 `UserController` 缺少 `use Flight;` 导入**
- 🐛 **修复修改密码页面加载用户信息失败**：补全 `loadUserInfo()` 函数
- ✅ 修复用户列表 500 错误
- ✅ 修复右上角显示"加载中..."不更新的问题

---

## [1.0.3] - 2026-01-23

### 新增
- ✅ 添加 Views 视图开发完整示例
- ✅ 新增文章列表和详情页面（`/articles`、`/article/{id}`）
- ✅ 新增首页（`/`）展示框架特性
- ✅ 新增 404 错误页面
- ✅ 新增 ArticleController 支持页面和 API 两种模式
- ✅ QUICKSTART 文档增加 Views 开发教程

### 优化
- ✅ JSON 输出支持中文显示（`JSON_UNESCAPED_UNICODE`）
- ✅ 配置 Flight 视图路径
- ✅ database.sql 增加 5 篇示例文章数据

### 修复
- 🐛 修复 AuthMiddleware 缺少 `use Flight;` 导致的错误
- 🐛 修复 `success()` 和 `error()` 函数未停止执行的问题
- 🐛 **修复表名前缀重复问题**：代码中使用不带 `og_` 的表名，让 Medoo 自动添加前缀
- ✅ 中间件调用 `error()` 后不再继续执行后续代码
- ✅ 更新所有文档示例，统一表名使用规范

---

## [1.0.2] - 2026-01-23

### 新增
- ✅ 添加服务器配置文档 `SERVER_CONFIG.md`
- ✅ 详细的 Nginx 和 Apache 伪静态配置说明
- ✅ phpstudy 快速配置指南
- ✅ 完整的生产环境配置示例

### 优化
- ✅ 更新 `public/.htaccess` 添加详细注释
- ✅ README 和 QUICKSTART 添加伪静态配置重要提示
- ✅ 添加常见问题解决方案

### 修复
- 🐛 修复 debug 模式配置不生效的问题
- ✅ 生产环境（`APP_DEBUG=false`）不再显示错误详情
- ✅ 错误信息会记录到 `runtime/logs/` 日志文件

---

## [1.0.1] - 2026-01-23

### 新增
- ✅ 添加 `.env` 环境变量支持
- ✅ 添加跨域中间件 `CorsMiddleware`
- ✅ 添加跨域配置文件 `config/cors.php`
- ✅ 管理后台 API 独立目录 `app/api/admin/`

### 优化
- ✅ `storage` 目录重命名为 `runtime`（更清晰）
- ✅ 管理后台 API 路由统一使用 `/api/admin/` 前缀
- ✅ 前端业务 API 放在 `/api/` 目录
- ✅ 配置文件支持从 `.env` 读取
- ✅ 所有前端页面更新为新的 API 路径

### 目录结构变化

```
app/api/
├── admin/                  # 新增：管理后台 API
│   ├── AuthController.php
│   └── UserController.php
├── AuthController.php      # 前端用户认证
└── HealthController.php

app/middleware/
├── AuthMiddleware.php
└── CorsMiddleware.php      # 新增：跨域中间件

app/config/
├── app.php
├── database.php
└── cors.php                # 新增：跨域配置

app/helpers/
├── env.php                 # 新增：环境变量读取
└── functions.php

runtime/                    # 原 storage/
└── logs/
```

### API 路由变化

**旧路由** → **新路由**

- `POST /api/login` → `POST /api/admin/login` (管理员登录)
- `GET /api/info` → `GET /api/admin/info` (管理员信息)
- `GET /api/users` → `GET /api/admin/users` (用户列表)
- `POST /api/user` → `POST /api/admin/user` (创建用户)
- `PUT /api/user/{id}` → `PUT /api/admin/user/{id}` (更新用户)
- `DELETE /api/user/{id}` → `DELETE /api/admin/user/{id}` (删除用户)

**保持不变**
- `GET /api/health` (健康检查)

**新增前端业务接口**
- `POST /api/login` (前端用户登录)
- `GET /api/info` (前端用户信息)
- `POST /api/logout` (前端用户退出)

---

## [1.0.0] - 2026-01-23

### 初始版本
- ✅ Flight + Medoo + Layui 基础框架
- ✅ 用户管理 CRUD
- ✅ 管理员登录
- ✅ 权限中间件
- ✅ 完整的后台管理界面
