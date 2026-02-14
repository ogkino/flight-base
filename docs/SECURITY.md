# 安全指南

> **轻量级 ≠ 不安全！**

Flight Base 2.0 提供了完善的安全措施，适合生产环境使用。

---

## 🔒 已实现的安全措施

### 1. 认证与授权

#### JWT Token 认证

```php
// 登录时生成 Token
$token = generateToken([
    'id' => $user['id'],
    'type' => 'admin'
], 7 * 24 * 3600);  // 7天有效期

// 验证 Token
$payload = validateToken($token);
```

**特点**：
- ✅ Token 过期自动失效
- ✅ 无状态，易于扩展
- ✅ 支持多端登录

#### 权限中间件

```php
// 检查是否登录
AuthMiddleware::check();

// 检查管理员权限
AuthMiddleware::checkAdmin();
```

**保护**：
- ✅ 验证 Token 有效性
- ✅ 检查用户状态
- ✅ 自动刷新用户信息

#### 权限系统（v2.2.0 新增）

**权限模型**：基于 JSON 的轻量级权限配置

```php
// 验证权限
checkPermission('users', 'delete');  // 验证是否有"用户管理-删除"权限

// 检查权限
if (hasPermission($adminId, 'articles', 'create')) {
    // 有权限
}

// 获取管理员权限
$permissions = getAdminPermissions($adminId);
```

**权限配置格式**（存储在 `admin.permissions` 字段）：

```json
{
    "users": ["list", "create", "update", "delete"],
    "articles": ["list", "create", "update"]
}
```

**权限规则**：
- ✅ **超级管理员**（ID=1）：`permissions` 为 `NULL`，拥有所有权限，无法修改
- ✅ **普通管理员**：根据 `permissions` 配置决定权限
- ✅ **无权限**：`permissions` 为空或不包含该模块/操作时，返回 403
- ✅ **粒度**：精确到模块（如 `users`）和操作（如 `delete`）

**权限操作**：
- `list` - 查看列表
- `create` - 新增
- `update` - 编辑
- `delete` - 删除

**特殊保护**：
- ✅ ID=1 的超级管理员禁止删除
- ✅ 超级管理员无法修改权限和状态（始终拥有所有权限）

---

### 2. SQL 注入防护

#### Medoo 预处理（自动）

```php
// ✅ 安全：Medoo 自动使用预处理
$users = $db->select('users', '*', [
    'username' => $username  // 自动转义
]);

// ❌ 危险：直接拼接 SQL（不要这样做！）
$sql = "SELECT * FROM users WHERE username = '{$username}'";
```

#### 额外检测

```php
// 检测可疑输入
if (detectSqlInjection($input)) {
    error('非法输入');
}
```

---

### 3. XSS 防护

#### 输入过滤

```php
// 清理用户输入
$username = cleanInput(getPost('username'));
$content = cleanInput(getPost('content'));
```

**功能**：
- ✅ 移除 HTML 标签
- ✅ 转义特殊字符
- ✅ 防止脚本注入

#### 输出转义

```php
// 在模板中输出时也要转义
<?php echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); ?>
```

---

### 4. CSRF 防护

#### 生成 Token

```php
// 表单中添加 CSRF Token
$csrfToken = generateCsrfToken();
<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
```

#### 验证 Token

```php
// 提交时验证
$token = getPost('csrf_token');
if (!validateCsrfToken($token)) {
    error('CSRF 验证失败');
}
```

---

### 5. 密码安全

#### 强加密

```php
// 加密（自动使用 bcrypt）
$hashedPassword = hashPassword($password);

// 验证
if (verifyPassword($inputPassword, $hashedPassword)) {
    // 密码正确
}
```

**特点**：
- ✅ 使用 `password_hash()`（bcrypt）
- ✅ 自动加盐
- ✅ 防暴力破解

#### 密码强度验证

```php
if (!validatePassword($password)) {
    error('密码长度至少6位');
}

// 可扩展为更强验证
function validateStrongPassword($password) {
    // 至少8位，包含大小写字母、数字、特殊字符
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}
```

---

### 6. 输入验证

#### 邮箱验证

```php
if (!validateEmail($email)) {
    error('邮箱格式不正确');
}
```

#### 手机号验证（中国大陆）

```php
if (!validatePhone($phone)) {
    error('手机号格式不正确');
}
```

#### 用户名验证

```php
if (!validateUsername($username)) {
    error('用户名格式不正确（字母、数字、下划线，4-20位）');
}
```

---

### 7. 限流保护

#### 防止暴力攻击

```php
// 限制 1 小时内最多 10 次尝试
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!checkRateLimit('login_' . $clientIp, 10, 3600)) {
    error('登录尝试过于频繁，请1小时后再试');
}
```

**应用场景**：
- 登录
- 注册
- 发送验证码
- API 调用

---

### 8. 文件上传安全

#### 验证文件

```php
$result = validateUploadFile($_FILES['file'], [
    'image/jpeg',
    'image/png'
], 2097152);  // 2MB

if (!$result['success']) {
    error($result['msg']);
}
```

**检查项**：
- ✅ 文件大小
- ✅ MIME 类型（真实检测，不依赖扩展名）
- ✅ 上传错误

#### 安全存储

```php
// 生成随机文件名
$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$filename = md5(uniqid() . time()) . '.' . $ext;

// 存储到 uploads 目录
$uploadPath = __DIR__ . '/../public/uploads/' . date('Ymd') . '/';
if (!is_dir($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}

move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath . $filename);
```

---

### 9. CORS 跨域控制

#### 配置白名单

```php
// app/config/cors.php
return [
    'allowed_origins' => [
        'https://yourdomain.com',
        'https://admin.yourdomain.com'
    ],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization']
];
```

#### 中间件自动处理

```php
CorsMiddleware::handle();
```

---

### 10. 审计日志

#### 记录关键操作

```php
auditLog('创建用户', [
    'user_id' => $userId,
    'username' => $username,
    'ip' => $_SERVER['REMOTE_ADDR']
]);

auditLog('删除文章', [
    'article_id' => $articleId,
    'title' => $title
]);
```

**记录内容**：
- 操作人
- 操作时间
- 操作类型
- IP 地址
- User-Agent
- 详细参数

---

## 🛡️ 生产环境安全清单

### 部署前检查

- [ ] **关闭调试模式**：`.env` 中 `APP_DEBUG=false`
- [ ] **修改默认密码**：管理员账号密码
- [ ] **配置 HTTPS**：启用 SSL 证书
- [ ] **配置防火墙**：限制不必要的端口
- [ ] **启用日志**：记录所有异常和关键操作
- [ ] **配置备份**：数据库定时备份
- [ ] **限制文件权限**：`runtime/` 可写，其他只读
- [ ] **隐藏版本信息**：移除 `X-Powered-By` 等头部

---

## 🔐 安全最佳实践

### 1. 控制器层面

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class UserController
{
    public static function create()
    {
        // 1️⃣ 权限验证
        AuthMiddleware::checkAdmin();
        
        // 2️⃣ 输入清理
        $username = cleanInput(getPost('username'));
        $email = cleanInput(getPost('email'));
        
        // 3️⃣ 格式验证
        if (!validateUsername($username)) {
            error('用户名格式不正确');
        }
        
        if (!validateEmail($email)) {
            error('邮箱格式不正确');
        }
        
        // 4️⃣ 限流检查
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!checkRateLimit('create_user_' . $ip, 10, 3600)) {
            error('操作过于频繁');
        }
        
        // 5️⃣ 业务逻辑
        $db = db();
        
        // 检查重复
        if ($db->has('users', ['username' => $username])) {
            error('用户名已存在');
        }
        
        // 创建用户
        $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => hashPassword(getPost('password'))
        ]);
        
        // 6️⃣ 审计日志
        auditLog('创建用户', ['username' => $username]);
        
        // 7️⃣ 返回结果
        success(['id' => $db->id()], '创建成功');
    }
}
```

---

### 2. 数据库层面

```sql
-- 使用最小权限原则
-- 应用账号只给必要权限，不要用 root

GRANT SELECT, INSERT, UPDATE, DELETE ON flight_base.* TO 'app_user'@'localhost';
FLUSH PRIVILEGES;
```

---

### 3. 服务器层面

#### Nginx 安全配置

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    # SSL 证书
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # 安全头部
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # 隐藏版本信息
    server_tokens off;
    
    # 限制请求大小
    client_max_body_size 10M;
    
    # 限制请求频率
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    location /api/ {
        limit_req zone=api burst=20;
    }
    
    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(sql|log|env)$ {
        deny all;
    }
}
```

---

### 4. PHP 配置

```ini
; php.ini 安全配置

; 关闭错误显示
display_errors = Off
log_errors = On
error_log = /path/to/php-errors.log

; 禁用危险函数
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; 会话安全
session.cookie_httponly = 1
session.cookie_secure = 1  ; 启用 HTTPS 后
session.use_strict_mode = 1

; 文件上传限制
upload_max_filesize = 10M
post_max_size = 10M

; 防止信息泄露
expose_php = Off
```

---

## 🚨 常见安全问题及解决方案

### 1. SQL 注入

**问题**：
```php
// ❌ 危险
$sql = "SELECT * FROM users WHERE id = {$_GET['id']}";
```

**解决**：
```php
// ✅ 安全
$user = $db->get('users', '*', ['id' => $_GET['id']]);
```

---

### 2. XSS 攻击

**问题**：
```php
// ❌ 危险
echo $_POST['content'];
```

**解决**：
```php
// ✅ 安全
echo htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');

// 或使用辅助函数
$content = cleanInput($_POST['content']);
```

---

### 3. CSRF 攻击

**问题**：表单没有 CSRF 保护

**解决**：
```html
<!-- 表单中添加 Token -->
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <!-- 其他字段 -->
</form>
```

```php
// 验证 Token
if (!validateCsrfToken($_POST['csrf_token'])) {
    error('CSRF 验证失败');
}
```

---

### 4. 暴力破解

**问题**：无限次登录尝试

**解决**：
```php
// 限流
$ip = $_SERVER['REMOTE_ADDR'];
if (!checkRateLimit('login_' . $ip, 5, 300)) {
    error('登录尝试过多，请5分钟后再试');
}
```

---

### 5. 文件上传漏洞

**问题**：上传 PHP 文件执行

**解决**：
```php
// 1. 验证 MIME 类型（真实检测）
$result = validateUploadFile($_FILES['file'], ['image/jpeg', 'image/png']);

// 2. 重命名文件
$filename = md5(uniqid()) . '.jpg';

// 3. 存储在 public 之外或配置 Nginx 禁止执行
location /uploads/ {
    location ~ \.php$ {
        deny all;
    }
}
```

---

## 📚 总结

### 安全层次

```
┌─────────────────────────┐
│  7. 监控与审计           │  审计日志、异常告警
├─────────────────────────┤
│  6. 服务器安全           │  防火墙、SSL、Nginx 配置
├─────────────────────────┤
│  5. 应用层防护           │  限流、CSRF、XSS
├─────────────────────────┤
│  4. 业务逻辑验证         │  输入验证、权限检查
├─────────────────────────┤
│  3. 数据访问层           │  SQL 防注入、ORM
├─────────────────────────┤
│  2. 认证授权             │  JWT、权限中间件
├─────────────────────────┤
│  1. 数据加密             │  密码哈希、HTTPS
└─────────────────────────┘
```

### 核心原则

1. **最小权限**：只给必要的权限
2. **输入验证**：永远不信任用户输入
3. **输出转义**：防止 XSS
4. **纵深防御**：多层安全措施
5. **审计日志**：记录关键操作
6. **定期更新**：及时修复安全漏洞

---

**Flight Base 2.0 = 轻量 + 安全 + 强大！**
