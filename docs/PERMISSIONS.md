# 权限系统使用指南

> **版本**：v2.2.0  
> **类型**：基于 JSON 的轻量级权限管理

---

## 🎯 权限模型

### 三级权限结构

```
管理员（admin_id）
  └─ 模块（module）- 如：users、articles
       └─ 操作（action）- 如：list、create、update、delete
```

### 权限存储

**位置**：`og_admin` 表的 `permissions` 字段（TEXT，JSON 格式）

**格式**：

```json
{
    "users": ["list", "create", "update", "delete"],
    "articles": ["list", "create", "update"],
    "products": ["list"]
}
```

**说明**：
- `users`、`articles`、`products` - 模块名（对应 `CrudConfig` 中的配置方法名）
- `list`、`create`、`update`、`delete` - 操作名（CRUD 四大操作）

---

## 👑 超级管理员

### 特殊规则

- **ID**：固定为 `1`
- **权限**：`permissions` 字段为 `NULL`
- **能力**：拥有所有权限，不受任何限制
- **保护**：
  - ✅ 禁止删除
  - ✅ 无法修改权限配置（始终为 NULL）
  - ✅ 无法修改状态（始终为启用）
  - ✅ 可以修改基本信息（昵称、邮箱、密码等）

### 判断逻辑

```php
// ID=1 的管理员
if ($admin['id'] == 1) {
    return true;  // 所有权限检查都返回 true
}
```

---

## 🔐 权限验证

### 1. 控制器中验证

**推荐方式**（自动返回错误）：

```php
public static function delete($id)
{
    AuthMiddleware::checkAdmin();  // 1. 验证管理员身份
    checkPermission('users', 'delete');  // 2. 验证权限
    
    // 业务逻辑...
}
```

**返回结果**：
- ✅ 有权限：继续执行业务逻辑
- ❌ 无权限：返回 `{"code": 403, "msg": "无权限访问"}` 并终止

---

### 2. 手动检查权限

```php
$adminId = Flight::get('admin_id');

if (hasPermission($adminId, 'users', 'delete')) {
    // 有权限，执行操作
} else {
    // 无权限，返回错误
    error('无权限访问', 403);
}
```

---

### 3. 菜单权限过滤（v2.2.0 hotfix3 新增）

**自动过滤**：系统会根据管理员权限自动过滤菜单，无权限的菜单项不会显示。

**过滤规则**：
- ✅ **超级管理员**（ID=1）：显示所有菜单
- ✅ **普通管理员**：只显示有权限的菜单项
- ✅ **特殊页面**：仪表盘、修改密码始终显示
- ✅ **权限判断**：只要有模块的任意一个权限（list/create/update/delete），就显示该菜单项

**示例**：

假设管理员 test 的权限配置为：
```json
{
    "users": ["list", "create"],
    "articles": ["list"]
}
```

**菜单显示**：
- ✅ 仪表盘（始终显示）
- ✅ 用户管理（有 list 和 create 权限）
- ✅ 文章管理（有 list 权限）
- ❌ 管理员管理（无权限，不显示）
- ✅ 修改密码（始终显示）

**好处**：
- ✅ 用户体验更好（不会看到无权限的菜单）
- ✅ 更安全（减少信息泄露）
- ✅ 界面更简洁（只显示可用功能）

---

## 📋 权限操作列表

### 标准操作

| 操作名 | 说明 | 对应接口 |
|--------|------|---------|
| `list` | 查看列表 | `GET /api/admin/users` |
| `create` | 新增 | `POST /api/admin/user` |
| `update` | 编辑 | `POST /api/admin/user/:id` |
| `delete` | 删除 | `DELETE /api/admin/user/:id` |

### 自定义操作（可扩展）

如果需要更细粒度的权限，可以自定义操作：

```php
// 导出操作
checkPermission('users', 'export');

// 导入操作
checkPermission('users', 'import');

// 审核操作
checkPermission('articles', 'approve');
```

---

## 🛠️ 使用示例

### 示例 1：添加新模块权限

#### 步骤 1：在 CrudConfig 中定义模块

```php
public static function products()
{
    return [
        'page' => ['title' => '商品管理'],
        // ... 配置
    ];
}
```

#### 步骤 2：在控制器中验证权限

```php
public static function list()
{
    AuthMiddleware::checkAdmin();
    checkPermission('products', 'list');  // 自动识别模块名
    
    // 业务逻辑...
}
```

#### 步骤 3：配置管理员权限

后台 → 系统管理 → 管理员管理 → 编辑 → 权限配置：

- ✅ 勾选"商品管理 - 查看列表"
- ✅ 勾选"商品管理 - 新增"
- ✅ 保存

#### 完成

该管理员现在可以查看和新增商品，但无法编辑和删除。

---

### 示例 2：检查当前管理员权限

```php
$adminId = Flight::get('admin_id');

// 获取所有权限
$permissions = getAdminPermissions($adminId);

if ($permissions === 'all') {
    // 超级管理员
    echo '拥有所有权限';
} else {
    // 普通管理员
    print_r($permissions);
    // 输出：['users' => ['list', 'create'], 'articles' => ['list']]
}
```

---

### 示例 3：禁止删除超级管理员

```php
public static function delete($id)
{
    // 禁止删除 ID=1
    if ($id == 1) {
        error('禁止删除超级管理员', 403);
        return;
    }
    
    // 权限验证
    checkPermission('admins', 'delete');
    
    // 删除逻辑...
}
```

---

## 🎨 权限配置界面

### 界面说明

后台 → 系统管理 → 管理员管理 → 编辑 → **权限配置**

**显示效果**：

```
权限配置
┌─────────────────────────────┐
│ 📦 用户管理                  │
│   ☑ 查看列表  ☑ 新增        │
│   ☑ 编辑     ☑ 删除         │
├─────────────────────────────┤
│ 📦 文章管理                  │
│   ☑ 查看列表  ☑ 新增        │
│   ☐ 编辑     ☐ 删除         │
└─────────────────────────────┘
```

### 自动生成规则

权限选项自动从 `CrudConfig::getMenus()` 获取：

1. 遍历所有菜单项
2. 排除特殊页面（`dashboard`、`changePassword`）
3. 为每个模块生成 4 个操作（list、create、update、delete）

---

## 🔧 辅助函数

### hasPermission()

**功能**：检查管理员是否有指定权限

**参数**：
- `$adminId` - 管理员 ID
- `$module` - 模块名（如：users、articles）
- `$action` - 操作名（如：list、create、update、delete）

**返回**：`true` 或 `false`

**示例**：

```php
if (hasPermission($adminId, 'users', 'delete')) {
    echo '有删除用户的权限';
}
```

---

### checkPermission()

**功能**：验证权限，无权限时自动返回错误并终止

**参数**：
- `$module` - 模块名
- `$action` - 操作名

**示例**：

```php
public static function delete($id)
{
    AuthMiddleware::checkAdmin();
    checkPermission('users', 'delete');  // 无权限会自动返回 403
    
    // 有权限才会执行到这里
    $db->delete('users', ['id' => $id]);
}
```

---

### getAdminPermissions()

**功能**：获取管理员的所有权限配置

**参数**：
- `$adminId` - 管理员 ID

**返回**：
- 超级管理员：`'all'`（字符串）
- 普通管理员：`['users' => ['list', 'create'], ...]`（数组）
- 无权限：`[]`（空数组）

**示例**：

```php
$permissions = getAdminPermissions($adminId);

if ($permissions === 'all') {
    echo '超级管理员';
} else if (empty($permissions)) {
    echo '无任何权限';
} else {
    echo '拥有 ' . count($permissions) . ' 个模块的权限';
}
```

---

### getAllPermissions()

**功能**：获取系统中所有可用的权限配置

**返回**：

```php
[
    'users' => [
        'name' => '用户管理',
        'actions' => [
            'list' => '查看列表',
            'create' => '新增',
            'update' => '编辑',
            'delete' => '删除'
        ]
    ],
    'articles' => [
        'name' => '文章管理',
        'actions' => [...]
    ]
]
```

**用途**：在权限配置界面显示所有可选权限

---

## 🧪 测试权限系统

### 测试账号

**数据库默认包含两个管理员**：

| 用户名 | 密码 | 角色 | 权限 |
|--------|------|------|------|
| `admin` | `password` | 超级管理员 | 所有权限 |
| `test` | `password` | 测试管理员 | 部分权限 |

### 测试步骤

#### 1. 使用超级管理员登录

**账号**：`admin` / `password`

**测试**：
- ✅ 可以访问所有功能
- ✅ 可以查看/编辑/删除用户
- ✅ 可以查看/编辑/删除文章
- ✅ 可以管理其他管理员
- ✅ 无法删除自己（ID=1）

---

#### 2. 使用测试管理员登录

**账号**：`test` / `password`

**测试权限**（默认配置）：
- ✅ 用户管理：查看列表、新增、编辑
- ❌ 用户管理：删除（无权限）
- ✅ 文章管理：查看列表、新增、编辑、删除
- ❌ 管理员管理：无权限（未配置）

**测试步骤**：

1. 登录后台
2. 访问"用户管理"→ 点击"新增" ✅ 成功
3. 访问"用户管理"→ 点击"删除" ❌ 提示"无权限访问"
4. 访问"文章管理"→ 点击"删除" ✅ 成功
5. 访问"管理员管理" ❌ 提示"无权限访问"

---

#### 3. 修改权限配置

使用超级管理员（`admin`）登录：

1. 后台 → 系统管理 → 管理员管理
2. 找到"test"管理员，点击"编辑"
3. 在"权限配置"区域勾选更多权限
4. 点击"提交"
5. 退出，使用 `test` 账号重新登录
6. 验证新权限是否生效

---

## 🚨 安全建议

### 1. 最小权限原则

只给管理员分配必要的权限，不要给过多权限。

**反例**：
```json
{
    "users": ["list", "create", "update", "delete"],
    "articles": ["list", "create", "update", "delete"],
    "admins": ["list", "create", "update", "delete"]
}
```

**正例**：
```json
{
    "articles": ["list", "create", "update"]
}
```

---

### 2. 定期审查权限

建议每月审查管理员权限：

1. 检查是否有离职员工账号未删除
2. 检查权限是否过大
3. 检查是否有不活跃的管理员

---

### 3. 权限变更日志

所有权限变更都会记录在 `runtime/logs/` 中：

```
[2026-01-23 10:30:00] [info] 创建管理员：test
[2026-01-23 10:35:00] [info] 更新管理员 ID: 2
```

建议定期检查日志，发现异常及时处理。

---

### 4. 超级管理员账号保护

**建议**：
- ✅ 修改默认用户名（不要使用 `admin`）
- ✅ 使用强密码（至少 12 位，包含大小写字母、数字、特殊字符）
- ✅ 定期更换密码
- ✅ 限制超级管理员账号的使用场景（只在必要时使用）

---

## 📚 常见场景

### 场景 1：客服人员

**需求**：只能查看用户列表，不能修改

**权限配置**：
```json
{
    "users": ["list"]
}
```

---

### 场景 2：内容编辑

**需求**：可以管理文章，不能管理用户

**权限配置**：
```json
{
    "articles": ["list", "create", "update", "delete"]
}
```

---

### 场景 3：运营人员

**需求**：可以管理用户和文章，但不能删除

**权限配置**：
```json
{
    "users": ["list", "create", "update"],
    "articles": ["list", "create", "update"]
}
```

---

### 场景 4：部门主管

**需求**：可以管理本部门的所有功能

**权限配置**：
```json
{
    "users": ["list", "create", "update", "delete"],
    "articles": ["list", "create", "update", "delete"],
    "products": ["list", "create", "update", "delete"]
}
```

---

## ❓ 常见问题

### Q1：如何给管理员配置权限？

**A1**：使用超级管理员账号登录：

1. 后台 → 系统管理 → 管理员管理
2. 点击"编辑"
3. 在"权限配置"区域勾选权限
4. 点击"提交"

---

### Q2：为什么超级管理员看不到权限配置？

**A2**：超级管理员（ID=1）拥有所有权限且无法修改，所以编辑时不显示权限配置字段。

---

### Q3：如何添加新的操作权限？

**A3**：修改 `app/helpers/permission.php` 中的 `getAllPermissions()` 函数：

```php
$permissions[$page] = [
    'name' => $item['name'],
    'actions' => [
        'list' => '查看列表',
        'create' => '新增',
        'update' => '编辑',
        'delete' => '删除',
        'export' => '导出',  // 新增
        'import' => '导入'   // 新增
    ]
];
```

然后在控制器中验证：

```php
checkPermission('users', 'export');
```

---

### Q4：权限配置为空时，能访问哪些功能？

**A4**：权限配置为空（`NULL` 或 `{}`）时，**无法访问任何需要权限验证的功能**。

特殊页面不受权限限制：
- ✅ 仪表盘（`dashboard`）
- ✅ 修改密码（`changePassword`）

---

### Q5：如何实现更复杂的权限？

**A5**：当前权限系统是轻量级的，适合中小型项目。

如果需要更复杂的权限（如：角色、数据权限、动态权限），建议：

1. **角色权限**：创建 `roles` 表，管理员关联角色
2. **数据权限**：在权限中添加数据范围（如：只能看自己创建的）
3. **动态权限**：使用 RBAC（基于角色的访问控制）框架

**参考实现**（未来扩展）：
- 使用 `casbin/casbin-php` 实现 RBAC
- 或自行设计更复杂的权限表结构

---

## 📖 相关文档

- [SECURITY.md](SECURITY.md) - 安全指南
- [ADMIN_DEV.md](ADMIN_DEV.md) - 后台开发指南
- [CHANGELOG.md](CHANGELOG.md) - v2.2.0 更新日志

---

**简单高效的权限系统，让你的后台更安全！** 🔐
