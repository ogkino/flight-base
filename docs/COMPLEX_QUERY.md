# 复杂业务查询指南

> **Medoo 完全支持复杂查询！**

## 🎯 核心理解

**CRUD 配置 ≠ 限制业务逻辑！**

- ✅ CRUD 配置只影响**前端 UI 显示**
- ✅ 控制器里可以写**任何复杂业务逻辑**
- ✅ Medoo 支持 JOIN、子查询、聚合、事务等

---

## 📚 Medoo 复杂查询示例

### 1. JOIN 关联查询

#### 示例：查询文章及其作者信息

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class ArticleController
{
    /**
     * 文章列表（关联作者）
     */
    public static function listWithAuthor()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        
        // LEFT JOIN 查询
        $articles = $db->select('articles', [
            // JOIN 语法
            '[>]admin' => ['author_id' => 'id']  // articles.author_id = admin.id
        ], [
            // 选择字段
            'articles.id',
            'articles.title',
            'articles.content',
            'articles.created_at',
            'admin.username(author_name)',  // 别名
            'admin.email(author_email)'
        ], [
            // 条件
            'articles.status' => 1,
            'ORDER' => ['articles.id' => 'DESC'],
            'LIMIT' => 10
        ]);
        
        success($articles);
    }
}
```

#### JOIN 类型

```php
// LEFT JOIN
'[>]admin' => ['author_id' => 'id']

// RIGHT JOIN
'[<]admin' => ['author_id' => 'id']

// INNER JOIN
'[><]admin' => ['author_id' => 'id']

// FULL JOIN
'[<>]admin' => ['author_id' => 'id']
```

---

### 2. 多表 JOIN

#### 示例：文章 + 作者 + 分类

```php
public static function listWithDetails()
{
    $db = db();
    
    $articles = $db->select('articles', [
        // 多个 JOIN
        '[>]admin' => ['author_id' => 'id'],
        '[>]categories' => ['category_id' => 'id']
    ], [
        'articles.id',
        'articles.title',
        'admin.username(author_name)',
        'categories.name(category_name)'
    ]);
    
    success($articles);
}
```

---

### 3. 子查询

#### 示例：查询有文章的作者

```php
public static function authorsWithArticles()
{
    $db = db();
    
    // 子查询
    $authors = $db->select('admin', '*', [
        'id[>]' => $db->select('articles', 'author_id', [
            'GROUP' => 'author_id'
        ])
    ]);
    
    success($authors);
}
```

---

### 4. 聚合查询

#### 示例：统计每个作者的文章数

```php
public static function articleCountByAuthor()
{
    $db = db();
    
    $stats = $db->select('articles', [
        '[>]admin' => ['author_id' => 'id']
    ], [
        'admin.username',
        'COUNT(articles.id)(article_count)'
    ], [
        'GROUP' => 'articles.author_id'
    ]);
    
    success($stats);
}
```

---

### 5. 复杂条件查询

#### 示例：多条件搜索

```php
public static function search()
{
    $db = db();
    $keyword = getQuery('keyword');
    $status = getQuery('status');
    $startDate = getQuery('start_date');
    $endDate = getQuery('end_date');
    
    $where = [];
    
    // 关键词搜索（OR）
    if ($keyword) {
        $where['OR'] = [
            'title[~]' => $keyword,
            'content[~]' => $keyword
        ];
    }
    
    // 状态筛选
    if ($status !== null) {
        $where['status'] = $status;
    }
    
    // 日期范围
    if ($startDate && $endDate) {
        $where['created_at[<>]'] = [$startDate, $endDate];
    }
    
    $articles = $db->select('articles', '*', $where);
    
    success($articles);
}
```

---

### 6. 事务处理

#### 示例：创建文章并扣积分

```php
public static function createWithPoints()
{
    AuthMiddleware::checkAdmin();
    
    $db = db();
    
    try {
        // 开启事务
        $db->action(function($db) {
            // 插入文章
            $db->insert('articles', [
                'title' => getPost('title'),
                'content' => getPost('content'),
                'author_id' => currentUser()['id']
            ]);
            
            $articleId = $db->id();
            
            // 扣除积分
            $db->update('users', [
                'points[-]' => 10  // 减少 10 积分
            ], [
                'id' => currentUser()['id']
            ]);
            
            // 记录积分日志
            $db->insert('point_logs', [
                'user_id' => currentUser()['id'],
                'amount' => -10,
                'reason' => '发布文章',
                'ref_id' => $articleId
            ]);
            
            return $articleId;
        });
        
        success(['id' => $articleId], '发布成功');
        
    } catch (\Exception $e) {
        error('发布失败：' . $e->getMessage());
    }
}
```

---

### 7. 原生 SQL（极端情况）

#### 示例：复杂统计查询

```php
public static function complexStats()
{
    $db = db();
    
    // 如果 Medoo 语法无法满足，使用原生 SQL
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS month,
            COUNT(*) AS article_count,
            COUNT(DISTINCT author_id) AS author_count,
            AVG(LENGTH(content)) AS avg_length
        FROM og_articles
        WHERE status = 1
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ";
    
    $result = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    
    success($result);
}
```

---

### 8. 批量操作

#### 示例：批量更新状态

```php
public static function batchUpdateStatus()
{
    AuthMiddleware::checkAdmin();
    
    $db = db();
    $ids = getPost('ids', []); // [1, 2, 3, ...]
    $status = getPost('status');
    
    if (empty($ids)) {
        error('请选择要操作的数据');
    }
    
    // 批量更新
    $db->update('articles', [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ], [
        'id' => $ids  // WHERE id IN (1, 2, 3, ...)
    ]);
    
    // 审计日志
    auditLog('批量更新文章状态', [
        'ids' => $ids,
        'status' => $status
    ]);
    
    success([], '操作成功');
}
```

---

## 🔒 结合安全措施的完整示例

### 示例：安全的用户创建（输入验证 + 审计）

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class UserController
{
    /**
     * 创建用户（安全增强）
     */
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        
        // 1. 获取输入（XSS 防护）
        $username = cleanInput(getPost('username'));
        $email = cleanInput(getPost('email'));
        $phone = cleanInput(getPost('phone'));
        $password = getPost('password');
        
        // 2. 输入验证
        if (!validateUsername($username)) {
            error('用户名格式不正确（字母、数字、下划线，4-20位）');
        }
        
        if (!validateEmail($email)) {
            error('邮箱格式不正确');
        }
        
        if ($phone && !validatePhone($phone)) {
            error('手机号格式不正确');
        }
        
        if (!validatePassword($password)) {
            error('密码长度至少6位');
        }
        
        // 3. 限流检查
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!checkRateLimit('create_user_' . $clientIp, 10, 3600)) {
            error('操作过于频繁，请稍后再试');
        }
        
        // 4. 检查重复
        $db = db();
        $exists = $db->has('users', [
            'OR' => [
                'username' => $username,
                'email' => $email
            ]
        ]);
        
        if ($exists) {
            error('用户名或邮箱已存在');
        }
        
        // 5. 创建用户（事务）
        try {
            $userId = $db->action(function($db) use ($username, $email, $phone, $password) {
                // 插入用户
                $db->insert('users', [
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => hashPassword($password),
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $userId = $db->id();
                
                // 赠送新人积分
                $db->insert('point_logs', [
                    'user_id' => $userId,
                    'amount' => 100,
                    'reason' => '新用户注册奖励'
                ]);
                
                return $userId;
            });
            
            // 6. 审计日志
            auditLog('创建用户', [
                'user_id' => $userId,
                'username' => $username
            ]);
            
            // 7. 返回成功
            success(['id' => $userId], '创建成功');
            
        } catch (\Exception $e) {
            writeLog('创建用户失败: ' . $e->getMessage(), 'error');
            error('创建失败，请稍后重试');
        }
    }
}
```

---

## 📋 CRUD 配置与复杂业务的关系

### CRUD 配置（UI 层）

```php
// CrudConfig.php
public static function articles()
{
    return [
        'table' => [
            'url' => '/api/admin/articles',  // 调用哪个接口
            'cols' => [ /* 显示哪些列 */ ]
        ],
        'form' => [ /* 表单字段 */ ]
    ];
}
```

### 控制器（业务层）

```php
// ArticleController.php
public static function list()
{
    // 这里可以写任何复杂逻辑！
    // - JOIN 多表
    // - 复杂条件
    // - 聚合统计
    // - 事务处理
    // - 权限过滤
    // ...
    
    $articles = $db->select('articles', [
        '[>]admin' => ['author_id' => 'id'],
        '[>]categories' => ['category_id' => 'id']
    ], [ /* ... */ ]);
    
    layuiTable($articles, $count);
}
```

**关键**：CRUD 配置只定义前端如何显示，不限制后端业务逻辑！

---

## 🎯 总结

### Medoo 能力

| 功能 | 支持度 | 示例 |
|------|--------|------|
| JOIN（2-5 表） | ✅ 完全支持 | `'[>]admin' => ['author_id' => 'id']` |
| 子查询 | ✅ 支持 | `'id[>]' => $db->select(...)` |
| 聚合函数 | ✅ 支持 | `'COUNT(id)(total)'` |
| 事务 | ✅ 支持 | `$db->action(function($db) { ... })` |
| 原生 SQL | ✅ 支持 | `$db->query($sql)` |
| 批量操作 | ✅ 支持 | `'id' => [1, 2, 3]` |

### 安全措施

| 措施 | 实现 | 函数 |
|------|------|------|
| XSS 防护 | ✅ | `cleanInput()` |
| 输入验证 | ✅ | `validateEmail()`, `validatePhone()` |
| SQL 注入 | ✅ | Medoo 预处理 + `detectSqlInjection()` |
| 限流 | ✅ | `checkRateLimit()` |
| 审计日志 | ✅ | `auditLog()` |

---

## 🚀 结论

**Flight Base 2.0 = 轻量 + 安全 + 强大！**

- ✅ 前端配置驱动，节省 75% 代码
- ✅ 后端完全灵活，支持任何复杂业务
- ✅ 安全措施完善，生产环境可用
- ✅ AI 友好，快速开发

**轻量级 ≠ 功能弱！**
