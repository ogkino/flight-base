# 后台管理开发指南 2.0

> **配置驱动 · 零手写 · AI 友好**

## 🎯 核心理念

**后端配置 → 前端自动渲染 → 零手写代码**

不再需要手写表格、表单、搜索等重复代码，只需配置即可！

---

## 📖 重要提示

**第一次使用？** 请先阅读 [ARCHITECTURE.md](ARCHITECTURE.md) 了解整体架构！

**核心理解**：
- ✅ **配置（CrudConfig）**：定义前端 UI 如何显示
- ✅ **控制器（Controller）**：处理真正的业务逻辑
- ✅ **渲染器（CrudRenderer）**：自动生成 HTML/JS

**工作流程**：用户点击菜单 → 加载配置 → 自动渲染 UI → 调用 API → 控制器处理 → 返回数据

---

## 🛠️ CRUD 设计器 (推荐)

**Flight Base v2.3 新增可视化 CRUD 设计器！**

无需手写 `CrudConfig.php` 配置，通过可视化界面即可生成完整代码。

1. **访问设计器**：
   - 登录后台
   - 访问 `/admin/crud-designer.html`
   - 或点击侧边栏的「系统工具」->「CRUD 设计器」

2. **功能亮点**：
   - 🖱️ **拖拽式配置**：所见即所得的表单/表格配置
   - 📝 **自动生成代码**：一键生成 PHP 配置代码
   - 🔄 **细粒度控制**：支持新增/编辑时的显示、禁用、必填状态独立控制
   - 📅 **丰富字段类型**：
     - **基础**：文本、数字、密码、隐藏、多行文本
     - **选择**：下拉框 (支持动态数据)、单选、复选、开关
     - **时间**：日期、时间、日期时间、时间戳 (自动格式化)
     - **文件**：图片上传 (支持预览)、文件上传 (支持下载)、富文本编辑器
     - **高级 (v2.4)**：评分 (Rate)、滑块 (Slider)、颜色选择 (Color)、图标选择 (Icon)、标签输入 (Tags)
     - **表格列**：支持 状态标签、图标显示、超链接、进度条 等特殊渲染

3. **使用建议**：
   - 推荐使用设计器生成初始配置，复制到 `CrudConfig.php` 中
   - 复杂逻辑可在生成的代码基础上微调

---

## 🚀 快速开始

### 自动菜单配置（v2.1.6 新增）

**菜单自动化**：无需手动修改 HTML，菜单自动从配置生成！

#### 1. 配置系统名称

编辑 `app/config/app.php`：

```php
'admin_name' => '我的管理系统',  // 后台标题
```

或在 `.env` 中设置：

```
ADMIN_NAME=我的管理系统
```

#### 2. 配置菜单

编辑 `app/config/CrudConfig.php` 中的 `getMenus()` 方法：

```php
public static function getMenus()
{
    return [
        '仪表盘' => [
            'icon' => 'layui-icon-home',  // 父级图标（可选）
            'items' => [
                ['name' => '数据统计', 'page' => 'dashboard', 'icon' => 'layui-icon-chart']
            ]
        ],
        '内容管理' => [
            'icon' => 'layui-icon-template-1',  // 父级图标（可选）
            'items' => [
                ['name' => '文章管理', 'page' => 'articles', 'icon' => 'layui-icon-file'],
                ['name' => '分类管理', 'page' => 'categories', 'icon' => 'layui-icon-template']
            ]
        ],
        '系统管理' => [
            'icon' => 'layui-icon-set',  // 父级图标（可选）
            'items' => [
                ['name' => '用户管理', 'page' => 'users', 'icon' => 'layui-icon-user'],
                ['name' => '修改密码', 'page' => 'changePassword', 'icon' => 'layui-icon-password']
            ]
        ]
    ];
}
```

**配置说明**：

| 字段 | 说明 | 必填 |
|------|------|------|
| `icon` | 父级菜单图标（Layui 图标类名）| 否 |
| `items` | 子菜单项数组 | 是 |
| `items[].name` | 菜单显示名称 | 是 |
| `items[].page` | 页面标识（对应配置方法名）| 是 |
| `items[].icon` | 子菜单图标 | 是 |

**菜单渲染规则**：

- ✅ **只有 1 个子项**：直接显示为一级菜单（不显示下拉）
  - 示例：仪表盘、文章管理（如果单独配置）
  - 显示效果：`🏠 数据统计`

- ✅ **有 2+ 个子项**：显示为分组菜单（有下拉）
  - 示例：系统管理（包含用户管理、修改密码）
  - 显示效果：`⚙️ 系统管理 ▼`

- ✅ **父级图标**：
  - 如果配置了 `icon`，使用配置的图标
  - 如果没有配置，使用第一个子项的图标
  - 只在多项分组时显示

**向后兼容**：

旧格式（不推荐，但仍支持）：

```php
'内容管理' => [
    ['name' => '文章管理', 'page' => 'articles', 'icon' => 'layui-icon-file']
]
```

新格式（推荐）：

```php
'内容管理' => [
    'icon' => 'layui-icon-template-1',
    'items' => [
        ['name' => '文章管理', 'page' => 'articles', 'icon' => 'layui-icon-file']
    ]
]
```

#### 3. 添加新功能模块

只需两步：

1. **在 `getMenus()` 中添加菜单项**
2. **添加对应的配置方法**

前端会自动显示新菜单，无需修改 HTML！

---

### 完整 CRUD 开发示例

#### 步骤 1：定义配置

在 `app/config/CrudConfig.php` 中添加配置：

```php
public static function articles()
{
    return [
        'page' => [
            'title' => '文章管理',
            'icon' => 'layui-icon-template-1',
            'page' => 'articles'
        ],
        
        'table' => [
            'url' => '/api/admin/articles',
            'actionsWidth' => 200,
            'cols' => [
                ['field' => 'id', 'title' => 'ID', 'width' => 80],
                ['field' => 'title', 'title' => '标题', 'minWidth' => 200],
                ['field' => 'author', 'title' => '作者', 'width' => 120]
            ]
        ],

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
            ]
        ],

        'search' => [
            [
                'type' => 'input',
                'name' => 'keyword',
                'placeholder' => '搜索文章',
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
            ]
        ],
        
        'form' => [
            ['type' => 'input', 'name' => 'title', 'label' => '标题', 'required' => true],
            ['type' => 'textarea', 'name' => 'content', 'label' => '内容', 'rows' => 10]
        ]
    ];
}
```

#### 步骤 2：在菜单中添加

在 `app/config/CrudConfig.php` 的 getMenus() 里添加菜单

#### 完成！✅

无需任何其他代码，自动生成：
- ✅ 表格（分页、排序）
- ✅ 搜索框
- ✅ 新增/编辑弹窗
- ✅ 删除确认

---

## 📋 配置参考

### 完整配置示例

```php
return [
    // ========== 页面信息 ==========
    'page' => [
        'title' => '用户管理',           // 页面标题
        'icon' => 'layui-icon-user',    // 图标
        'type' => 'crud',               // 页面类型：crud（默认） | form
        'page' => 'users'               // 用于权限判断
    ],
    
    // ========== 表格配置 ==========
    'table' => [
        'url' => '/api/admin/users',    // 数据接口
        'page' => true,                  // 是否分页
        'limit' => 10,                   // 每页条数
        'cols' => [                      // 列配置
            [
                'field' => 'id',         // 字段名
                'title' => 'ID',         // 列标题
                'width' => 80,           // 列宽
                'sort' => true           // 是否可排序
            ],
            [
                'field' => 'status',
                'title' => '状态',
                'width' => 100,
                'templet' => '#statusTpl' // 使用模板
            ]
        ]
    ],

    // ========== 行操作配置 ==========
    'actions' => [
        [
            'text' => '编辑',
            'action' => 'edit',         // 动作必须为 edit
            'icon' => 'layui-icon-edit',
            'class' => 'layui-btn-normal',
            'permission' => 'update' // 权限标识
        ],
        [
            'text' => '删除',
            'action' => 'delete',       // 动作必须为 delete
            'icon' => 'layui-icon-delete',
            'class' => 'layui-btn-danger',
            'permission' => 'delete' // 权限标识
        ],
        [
            'text' => '自定义按钮',
            'action' => 'preview',      //可随意定义
            'icon' => 'layui-icon-file',
            'class' => 'layui-btn-warm',
            'type' => 'iframe', // 使用 iframe 弹窗 或 page 跳转
            'url' => '/product/{id}',    //{id}为行id
            'width' => '1600px', // 弹窗宽度
            'height' => '80%',    // 弹窗高度
            'permission' => 'custom'    // 可定义查看权限标识
        ]
    ],
    
    // ========== 搜索配置 ==========
    'search' => [
        [
            'type' => 'input',            // 类型：input
            'name' => 'keyword',          // 字段名
            'placeholder' => '搜索...',   // 占位符
            'width' => 250                // 宽度
        ]
    ],
    
    // ========== 工具栏按钮 ==========
    'toolbar' => [
        [
            'text' => '新增',             // 按钮文本
            'icon' => 'layui-icon-add-1', // 图标
            'class' => 'layui-btn-normal',// 样式类
            'action' => 'add'             // 动作
        ],
        [
            'text' => '导出',             // 导出按钮
            'icon' => 'layui-icon-export',
            'class' => 'layui-btn-warm',
            'action' => 'export'          // 动作必须为 export
        ],
        [
            'text' => '自定义',
            'action' => 'preview',
            'permission' => 'custom',   //可设置权限
            'icon' => 'layui-icon-list',
            'class' => 'layui-btn-normal',
            'type' => 'iframe', // 使用 iframe 弹窗 或 page 跳转
            'url' => '/articles',
            'width' => '1600px', // 弹窗宽度
            'height' => '80%'    // 弹窗高度
        ]
    ],
    
    // ========== 表单配置 ==========
    'form' => [
        [
            'type' => 'input',            // 字段类型
            'name' => 'username',         // 字段名
            'label' => '用户名',          // 标签
            'required' => true,           // 是否必填
            'verify' => 'required'        // 验证规则
        ],
        [
            'type' => 'password',
            'name' => 'password',
            'label' => '密码',
            'placeholder' => '留空则不修改',
            'required' => 'add'           // 仅新增时必填
        ],
        [
            'type' => 'radio',            // 单选框
            'name' => 'status',
            'label' => '状态',
            'options' => [
                ['value' => 1, 'title' => '正常', 'checked' => true],
                ['value' => 0, 'title' => '禁用']
            ]
        ],
        [
            'type' => 'select',           // 下拉框
            'name' => 'role',
            'label' => '角色',
            'options' => [
                ['value' => 1, 'label' => '管理员'],
                ['value' => 2, 'label' => '编辑']
            ]
        ],
        [
            'type' => 'textarea',         // 文本域
            'name' => 'bio',
            'label' => '简介',
            'rows' => 5
        ]
    ],
    
    // ========== API 配置 ==========
    'api' => [
        'list' => '/api/admin/users',
        'add' => '/api/admin/user',
        'edit' => '/api/admin/user/{id}',
        'delete' => '/api/admin/user/{id}'
    ]
];
```

---

## 📝 字段类型

### input - 文本输入框

```php
[
    'type' => 'input',
    'name' => 'username',
    'label' => '用户名',
    'inputType' => 'text',        // text | email | number
    'placeholder' => '请输入',
    'required' => true
]
```

### password - 密码框

```php
[
    'type' => 'password',
    'name' => 'password',
    'label' => '密码',
    'required' => 'add'            // 仅新增时必填
]
```

### textarea - 文本域

```php
[
    'type' => 'textarea',
    'name' => 'content',
    'label' => '内容',
    'rows' => 10
]
```

### radio - 单选框

```php
[
    'type' => 'radio',
    'name' => 'status',
    'label' => '状态',
    'options' => [
        ['value' => 1, 'title' => '启用', 'checked' => true],
        ['value' => 0, 'title' => '禁用']
    ]
]
```

### select - 下拉框

```php
[
    'type' => 'select',
    'name' => 'category',
    'label' => '分类',
    'options' => [
        ['value' => 1, 'label' => '分类1'],
        ['value' => 2, 'label' => '分类2']
    ]
]
```

### datetime - 日期时间选择器

```php
[
    'type' => 'datetime',
    'name' => 'created_at',
    'label' => '创建时间',
    'format' => 'yyyy-MM-dd HH:mm:ss',
    'placeholder' => '请选择日期时间'
]
```

### timestamp - 时间戳 (自动转换)

**智能时间戳处理**

```php
[
    'type' => 'timestamp',
    'name' => 'updated_at',
    'label' => '更新时间',
    'required' => true
]
```

**特性**：
- **前端显示**：自动将 10 位时间戳格式化为 `yyyy-MM-dd HH:mm:ss`
- **表单提交**：自动将日期时间转换为 10 位秒级时间戳提交给后端
- **无需后端处理**：后端直接接收整数时间戳，无需手动转换格式

### time - 时间选择器

---

## 🎨 特殊页面类型

### 纯表单页面（如修改密码）

```php
return [
    'page' => [
        'title' => '修改密码',
        'icon' => 'layui-icon-password',
        'type' => 'form'              // 指定为纯表单
    ],
    
    'form' => [
        ['type' => 'password', 'name' => 'old_password', 'label' => '原密码'],
        ['type' => 'password', 'name' => 'new_password', 'label' => '新密码']
    ],
    
    'tips' => [                       // 提示信息
        'type' => 'info',
        'text' => '建议定期更换密码'
    ],
    
    'api' => [
        'submit' => '/api/admin/change-password'
    ]
];
```

---

## 🔌 添加新模块（完整流程）

### 示例：添加「商品管理」

#### 1. 创建数据表

```sql
CREATE TABLE `og_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2. 创建控制器 `app/api/admin/ProductController.php`

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class ProductController
{
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'list');
        
        $db = db();
        $page = (int)getQuery('page', 1);
        $limit = (int)getQuery('limit', 10);
        
        $count = $db->count('products');
        $products = $db->select('products', '*', [
            'LIMIT' => [($page - 1) * $limit, $limit]
        ]);
        
        layuiTable($products, $count);
    }
    
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'create');
        
        $db = db();
        $result = $db->insert('products', [
            'name' => getPost('name'),
            'price' => getPost('price'),
            'stock' => getPost('stock', 0)
        ]);
        
        if ($result->rowCount() > 0) {
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }
    
    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'update');
        
        $db = db();
        $result = $db->update('products', [
            'name' => getPost('name'),
            'price' => getPost('price'),
            'stock' => getPost('stock')
        ], ['id' => $id]);
        
        success([], '更新成功');
    }
    
    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'delete');
        
        $db = db();
        $db->delete('products', ['id' => $id]);
        
        success([], '删除成功');
    }

    /**
     * 导出数据（可选）
     */
    public static function export()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('products', 'export');
        
        $db = db();
        $products = $db->select('products', '*');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products.csv"');
        
        $fp = fopen('php://output', 'w');
        fwrite($fp, "\xEF\xBB\xBF"); // BOM
        fputcsv($fp, ['ID', '名称', '价格', '库存']);
        
        foreach ($products as $item) {
            fputcsv($fp, [$item['id'], $item['name'], $item['price'], $item['stock']]);
        }
        fclose($fp);
    }
}
```

#### 3. 注册路由 `public/index.php`

```php
// 商品管理
Flight::route('GET /api/admin/products', function(){
    \App\Api\Admin\ProductController::list();
});

Flight::route('POST /api/admin/product', function(){
    \App\Api\Admin\ProductController::create();
});

Flight::route('POST /api/admin/product/@id', function($id){
    \App\Api\Admin\ProductController::update($id);
});

Flight::route('GET /api/admin/products/export', function(){
    \App\Api\Admin\ProductController::export();
});

Flight::route('DELETE /api/admin/product/@id', function($id){
    \App\Api\Admin\ProductController::delete($id);
});
```

#### 4. 添加配置 `app/config/CrudConfig.php`

```php
public static function products()
{
    return [
        'page' => [
            'title' => '商品管理',
            'icon' => 'layui-icon-cart',
            'page' => 'products' 
        ],
        
        'table' => [
            'url' => '/api/admin/products',
            'actionsWidth' => 250,
            'cols' => [
                ['field' => 'id', 'title' => 'ID', 'width' => 80],
                ['field' => 'name', 'title' => '商品名称', 'minWidth' => 200],
                ['field' => 'price', 'title' => '价格', 'width' => 120],
                ['field' => 'stock', 'title' => '库存', 'width' => 100]
            ]
        ],

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
        
        'form' => [
            ['type' => 'input', 'name' => 'name', 'label' => '商品名称', 'required' => true],
            ['type' => 'input', 'name' => 'price', 'label' => '价格', 'inputType' => 'number', 'required' => true],
            ['type' => 'input', 'name' => 'stock', 'label' => '库存', 'inputType' => 'number']
        ],
        
        'api' => [
            'list' => '/api/admin/products',
            'add' => '/api/admin/product',
            'edit' => '/api/admin/product/{id}',
            'delete' => '/api/admin/product/{id}'
        ]
    ];
}
```

#### 5. 在 `app/config/CrudConfig.php` 的 getMenus() 里添加菜单

#### 完成！✅

刷新页面，点击「商品列表」即可看到完整的 CRUD 页面！

---

## 💡 AI 提示词示例

### 让 AI 帮你生成配置

**提示词**：

```
请为我生成一个"文章管理"的配置，包含以下字段：
- id（自动）
- title（标题，必填）
- content（内容，文本域）
- author（作者）
- category（分类，下拉：技术/生活/随笔）
- status（状态，单选：草稿/已发布）
- views（浏览量）
- created_at（创建时间）

按照 CrudConfig 格式生成配置代码。
```

AI 会自动生成完整配置，直接复制到 `CrudConfig.php` 即可！

---

## 🎯 最佳实践

### 1. 配置命名规范

```php
// 配置方法名与菜单 data-page 一致
public static function articles()  // data-page="articles"
public static function products()  // data-page="products"
```

### 2. 接口命名规范

```
GET    /api/admin/模块s           # 列表（复数）
POST   /api/admin/模块            # 创建（单数）
POST   /api/admin/模块/{id}       # 更新
DELETE /api/admin/模块/{id}       # 删除
```

### 3. 控制器结构

```php
class XxxController
{
    public static function list() {}      // 列表
    public static function create() {}    // 创建
    public static function update($id) {} // 更新
    public static function delete($id) {} // 删除
}
```

---

## 🔧 高级用法

### 自定义按钮与扩展

Flight Base v2.2 支持强大的自定义按钮功能，无论是行操作还是工具栏，都可以轻松扩展。

#### 1. 行操作按钮 (`actions`)

在配置中添加 `actions` 数组即可替代默认的操作列。

```php
'actions' => [
    // 标准编辑按钮
    [
        'text' => '编辑',
        'action' => 'edit',
        'icon' => 'layui-icon-edit',
        'class' => 'layui-btn-normal',
        'permission' => 'update' // 只有拥有 update 权限的用户可见
    ],
    // 自定义弹窗预览
    [
        'text' => '预览',
        'action' => 'preview',
        'icon' => 'layui-icon-file',
        'type' => 'iframe',       // 弹窗模式
        'url' => '/article/{id}', // {id} 会被替换为当前行数据
        'width' => '1000px',      // 弹窗宽度
        'height' => '80%'         // 弹窗高度
    ],
    // 自定义新窗口打开
    [
        'text' => '设计',
        'action' => 'design',
        'type' => 'page',         // 新窗口打开
        'url' => '/admin/design/{id}'
    ]
]
```

#### 2. 工具栏按钮 (`toolbar`)

工具栏按钮同样支持自定义扩展。

```php
'toolbar' => [
    [
        'text' => '新增',
        'action' => 'add',
        'icon' => 'layui-icon-add-1',
        'permission' => 'create'
    ],
    [
        'text' => '导出数据',
        'action' => 'export',     // 内置动作：自动调用导出接口
        'icon' => 'layui-icon-export',
        'class' => 'layui-btn-warm',
        'permission' => 'list'
    ],
    [
        'text' => '全局统计',
        'action' => 'stats',
        'type' => 'iframe',       // 自定义弹窗
        'url' => '/admin/stats/global',
        'width' => '800px',
        'height' => '600px'
    ]
]
```

#### 3. 自动 Token 注入

当使用 `type: 'iframe'` 或 `type: 'page'` 时，系统会自动在 URL 后追加 `token=xxx` 参数。
这意味着您在开发自定义控制器时，可以直接使用 `AuthMiddleware::checkAdmin()` 进行鉴权，无需担心 iframe 内无法获取登录状态。

### 自定义验证

前端自定义验证器（在 `crud-renderer.js` 中）：

```javascript
registerFormValidators(form) {
    form.verify({
        price: function(value) {
            if (value <= 0) {
                return '价格必须大于0';
            }
        }
    });
}
```

### 自定义模板

在 `index.html` 中添加自定义模板：

```html
<script type="text/html" id="priceTpl">
    ¥{{ d.price.toFixed(2) }}
</script>
```

在配置中使用：

```php
['field' => 'price', 'title' => '价格', 'templet' => '#priceTpl']
```

---

## 🎨 字段配置属性（完整参考）

### 通用属性

所有字段类型都支持的属性：

| 属性 | 类型 | 说明 | 示例 |
|------|------|------|------|
| `type` | string | **必填**，字段类型 | `'input'`, `'password'`, `'select'` 等 |
| `name` | string | **必填**，字段名（对应数据库字段） | `'username'`, `'email'` |
| `label` | string | **必填**，字段标签 | `'用户名'`, `'邮箱地址'` |
| `placeholder` | string | 输入提示 | `'请输入用户名'` |
| `tip` | string | 字段说明（显示在下方） | `'4-20位字母、数字、下划线'` |
| `default` | mixed | 默认值 | `''`, `0`, `1` |

### 字段显示/隐藏控制

| 属性 | 类型 | 说明 | 使用场景 |
|------|------|------|---------|
| `hidden_on_add` | bool | 新增时隐藏此字段 | 编辑时才需要的字段（如：最后修改时间）|
| `hidden_on_edit` | bool | 编辑时隐藏此字段 | 只在创建时需要的字段（如：邀请码）|
| `hidden_on_super_admin` | bool | 超级管理员编辑时隐藏 | 特殊权限控制（如：权限配置、状态）|

### 字段禁用控制

| 属性 | 类型 | 说明 | 使用场景 |
|------|------|------|---------|
| `disabled_on_add` | bool | 新增时禁用此字段 | 很少使用 |
| `disabled_on_edit` | bool | 编辑时禁用此字段 | 不允许修改的字段（如：用户名）|

### 字段必填控制

**新格式（推荐，更明确）**：

| 属性 | 类型 | 说明 | 效果 |
|------|------|------|------|
| `required_on_add` | bool | 只在新增时必填 | 新增：必填 ✅<br>编辑：非必填 ❌ |
| `required_on_edit` | bool | 只在编辑时必填 | 新增：非必填 ❌<br>编辑：必填 ✅ |
| `required_on_both` | bool | 新增和编辑都必填 | 新增：必填 ✅<br>编辑：必填 ✅ |

**旧格式（向后兼容）**：

| 属性值 | 效果 | 等价于新格式 |
|--------|------|-------------|
| `required: true` | 始终必填 | `required_on_both: true` |
| `required: 'add'` | 只在新增时必填 | `required_on_add: true` |
| `required: 'edit'` | 只在编辑时必填 | `required_on_edit: true` |
| `required: false` | 非必填 | （不设置任何 required）|

### 字段必填规则详解

#### 方式 1：新增和编辑都必填

```php
[
    'type' => 'input',
    'name' => 'nickname',
    'label' => '昵称',
    'required_on_both' => true  // ✅ 新增和编辑都必填
]

// 或使用旧格式（向后兼容）
[
    'type' => 'input',
    'name' => 'nickname',
    'label' => '昵称',
    'required' => true  // ✅ 效果相同
]
```

**使用场景**：
- ✅ 昵称、标题等核心字段

---

#### 方式 2：只在新增时必填

```php
[
    'type' => 'password',
    'name' => 'password',
    'label' => '密码',
    'required_on_add' => true,  // ✅ 只在新增时必填
    'tip' => '编辑时留空表示不修改密码'
]

// 或使用旧格式（向后兼容）
[
    'type' => 'password',
    'name' => 'password',
    'label' => '密码',
    'required' => 'add',  // ✅ 效果相同
    'tip' => '编辑时留空表示不修改密码'
]
```

**使用场景**：
- ✅ 密码字段（新增必填，编辑时留空表示不修改）
- ✅ 图片上传（新增必填，编辑时可选）
- ✅ 初始配置项（创建时必填，后续可选）

---

#### 方式 3：只在编辑时必填（v2.2.3 新增）

```php
[
    'type' => 'input',
    'name' => 'reject_reason',
    'label' => '拒绝原因',
    'required_on_edit' => true,  // ✅ 只在编辑时必填
    'hidden_on_add' => true      // ✅ 新增时隐藏
]

// 或使用旧格式（向后兼容）
[
    'type' => 'input',
    'name' => 'reject_reason',
    'label' => '拒绝原因',
    'required' => 'edit',  // ✅ 效果相同
    'hidden_on_add' => true
]
```

**使用场景**：
- ✅ 审核拒绝原因（编辑状态时必填）
- ✅ 修改备注（编辑时必须填写修改原因）
- ✅ 更新说明（编辑时必填）

---

#### 方式 4：非必填

```php
[
    'type' => 'input',
    'name' => 'phone',
    'label' => '手机号'
    // ✅ 不设置任何 required 属性，默认非必填
]
```

### 字段禁用规则详解

**编辑时禁用某些字段**：

```php
[
    'type' => 'input',
    'name' => 'username',
    'label' => '用户名',
    'required' => true,
    'disabled_on_edit' => true  // ✅ 编辑时禁用（不可修改，但会显示）
]
```

**效果**：
- ✅ 新增时：可以输入
- ✅ 编辑时：显示为灰色，不可修改
- ✅ 提交时：禁用的字段值不会被提交

**使用场景**：
- ✅ 用户名（创建后不允许修改）
- ✅ 唯一标识符（ID、编号等）

### 字段隐藏规则详解

#### 新增时隐藏（v2.2.3 新增）

```php
[
    'type' => 'input',
    'name' => 'last_modified_by',
    'label' => '最后修改人',
    'hidden_on_add' => true  // ✅ 新增时完全不显示
]
```

**效果**：
- ❌ 新增时：完全不显示
- ✅ 编辑时：显示并可以输入（或查看）

**使用场景**：
- ✅ 最后修改时间、修改人（新建时没有意义）
- ✅ 更新日志、版本号（编辑时才需要）
- ✅ 审核状态、拒绝原因（编辑时才有）

---

#### 编辑时隐藏

```php
[
    'type' => 'input',
    'name' => 'invite_code',
    'label' => '邀请码',
    'hidden_on_edit' => true  // ✅ 编辑时完全不显示
]
```

**效果**：
- ✅ 新增时：显示并可以输入
- ❌ 编辑时：完全不显示

**使用场景**：
- ✅ 创建时需要的参数（邀请码、推荐人等）
- ✅ 只在新增时有意义的字段
- ✅ 初始配置项（创建后不再显示）

### 超级管理员特殊规则

**超级管理员（ID=1）编辑时隐藏特定字段**：

```php
[
    'type' => 'permissions',
    'name' => 'permissions',
    'label' => '权限配置',
    'hidden_on_super_admin' => true  // ✅ ID=1 编辑时隐藏
]
```

**效果**：
- ✅ 编辑 ID=1 的管理员：不显示此字段
- ✅ 编辑其他管理员：正常显示

**使用场景**：
- ✅ 权限配置（超级管理员固定拥有所有权限）
- ✅ 状态开关（超级管理员固定为启用）

### 完整示例 1：管理员管理

```php
'form' => [
    // 用户名：始终必填，编辑时禁用
    [
        'type' => 'input',
        'name' => 'username',
        'label' => '用户名',
        'required_on_both' => true,      // 新格式：始终必填
        'disabled_on_edit' => true,      // 编辑时禁用（不可修改）
        'placeholder' => '4-20位字母、数字、下划线'
    ],
    
    // 密码：只在新增时必填
    [
        'type' => 'password',
        'name' => 'password',
        'label' => '密码',
        'required_on_add' => true,       // 新格式：只在新增时必填
        'placeholder' => '6-20位，包含字母和数字',
        'tip' => '编辑时留空表示不修改密码'
    ],
    
    // 昵称：始终必填
    [
        'type' => 'input',
        'name' => 'nickname',
        'label' => '昵称',
        'required_on_both' => true
    ],
    
    // 邮箱：非必填
    [
        'type' => 'input',
        'name' => 'email',
        'label' => '邮箱',
        'inputType' => 'email'
        // 不设置 required，默认非必填
    ],
    
    // 权限：超级管理员编辑时隐藏
    [
        'type' => 'permissions',
        'name' => 'permissions',
        'label' => '权限配置',
        'hidden_on_super_admin' => true  // ID=1 编辑时隐藏
    ],
    
    // 状态：超级管理员编辑时隐藏
    [
        'type' => 'switch',
        'name' => 'status',
        'label' => '状态',
        'text' => '启用|禁用',
        'hidden_on_super_admin' => true
    ]
]
```

---

### 完整示例 2：工单管理（展示所有规则）

```php
'form' => [
    // 标题：始终必填
    [
        'type' => 'input',
        'name' => 'title',
        'label' => '工单标题',
        'required_on_both' => true
    ],
    
    // 内容：始终必填
    [
        'type' => 'editor',
        'name' => 'content',
        'label' => '工单内容',
        'required_on_both' => true,
        'height' => '300px'
    ],
    
    // 优先级：新增必填，编辑可选
    [
        'type' => 'select',
        'name' => 'priority',
        'label' => '优先级',
        'required_on_add' => true,      // 只在新增时必填
        'options' => [
            ['value' => 'low', 'label' => '低'],
            ['value' => 'normal', 'label' => '普通'],
            ['value' => 'high', 'label' => '高'],
            ['value' => 'urgent', 'label' => '紧急']
        ]
    ],
    
    // 拒绝原因：编辑必填，新增隐藏
    [
        'type' => 'textarea',
        'name' => 'reject_reason',
        'label' => '拒绝原因',
        'required_on_edit' => true,     // 只在编辑时必填
        'hidden_on_add' => true,        // 新增时隐藏
        'placeholder' => '请输入拒绝原因',
        'rows' => 3
    ],
    
    // 最后处理人：编辑显示，新增隐藏
    [
        'type' => 'input',
        'name' => 'last_handler',
        'label' => '最后处理人',
        'hidden_on_add' => true,        // 新增时隐藏
        'disabled_on_edit' => true      // 编辑时禁用（只读）
    ],
    
    // 邀请码：新增显示，编辑隐藏
    [
        'type' => 'input',
        'name' => 'invite_code',
        'label' => '邀请码',
        'hidden_on_edit' => true        // 编辑时隐藏
    ]
]
```

**说明**：
- ✅ `title`、`content` - 始终必填
- ✅ `priority` - 新增必填，编辑可选
- ✅ `reject_reason` - 新增时隐藏，编辑时显示且必填（典型的审核场景）
- ✅ `last_handler` - 新增时隐藏，编辑时显示但只读
- ✅ `invite_code` - 新增时显示，编辑时隐藏（只在创建时有意义）

---

## 📊 特殊页面配置

### 仪表盘配置

**类型**：`type: 'dashboard'`（自动推断，可不写）

仪表盘是一个特殊的页面类型，用于展示统计数据和快捷入口。

#### 完整示例

```php
public static function dashboard()
{
    return [
        'page' => [
            'title' => '仪表盘',
            'icon' => 'layui-icon-home',
            'type' => 'dashboard'  // 可省略，自动推断
        ],
        
        // 统计卡片配置
        'stats' => [
            [
                'title' => '用户总数',           // 卡片标题
                'value' => 0,                    // 默认值（加载前显示）
                'icon' => 'layui-icon-user',     // 图标
                'color' => '#1E90FF',            // 颜色
                'url' => '/api/admin/stats/users'  // API 接口
            ],
            [
                'title' => '文章总数',
                'value' => 0,
                'icon' => 'layui-icon-file',
                'color' => '#FF6B6B',
                'url' => '/api/admin/stats/articles'
            ],
            [
                'title' => '今日浏览',
                'value' => 0,
                'icon' => 'layui-icon-chart',
                'color' => '#4ECDC4',
                'url' => '/api/admin/stats/views'
            ],
            [
                'title' => '系统状态',
                'value' => '正常',  // 可以是字符串
                'icon' => 'layui-icon-ok-circle',
                'color' => '#95E1D3',
                'url' => '/api/admin/stats/system'
            ]
        ],
        
        // 快捷入口配置（可选）
        'shortcuts' => [
            [
                'title' => '新增用户',
                'icon' => 'layui-icon-user',
                'color' => '#1E90FF',
                'page' => 'users',
                'action' => 'add'
            ],
            [
                'title' => '新增文章',
                'icon' => 'layui-icon-file',
                'color' => '#FF6B6B',
                'page' => 'articles',
                'action' => 'add'
            ]
        ]
    ];
}
```

#### 统计卡片配置说明

| 属性 | 类型 | 说明 |
|------|------|------|
| `title` | string | 卡片标题 |
| `value` | mixed | 默认值（可以是数字或字符串） |
| `icon` | string | Layui 图标类名 |
| `color` | string | 卡片颜色（CSS 颜色值） |
| `url` | string | API 接口地址 |

#### 统计接口返回格式

**数字统计**：

```php
// GET /api/admin/stats/users
success(['count' => 1234]);
```

**字符串统计**：

```php
// GET /api/admin/stats/system
success(['status' => '正常']);
```

#### 创建统计接口

**步骤 1：创建控制器**

```php
<?php
namespace App\Api\Admin;

use App\Middleware\AuthMiddleware;

class DashboardController
{
    // 用户总数
    public static function getUsersCount()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        $count = $db->count('users');
        
        success(['count' => $count]);
    }
    
    // 文章总数
    public static function getArticlesCount()
    {
        AuthMiddleware::checkAdmin();
        
        $db = db();
        $count = $db->count('articles');
        
        success(['count' => $count]);
    }
    
    // 系统状态
    public static function getSystemStatus()
    {
        AuthMiddleware::checkAdmin();
        
        success([
            'status' => '正常',
            'php_version' => PHP_VERSION,
            'memory' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
        ]);
    }
}
```

**步骤 2：注册路由**

```php
// public/index.php

Flight::route('GET /api/admin/stats/users', function(){
    \App\Api\Admin\DashboardController::getUsersCount();
});

Flight::route('GET /api/admin/stats/articles', function(){
    \App\Api\Admin\DashboardController::getArticlesCount();
});

Flight::route('GET /api/admin/stats/system', function(){
    \App\Api\Admin\DashboardController::getSystemStatus();
});
```

### 纯表单页面配置

**类型**：`type: 'form'`

用于修改密码、系统设置等不需要表格的页面。

#### 完整示例

```php
public static function changePassword()
{
    return [
        'page' => [
            'title' => '修改密码',
            'icon' => 'layui-icon-password',
            'type' => 'form'  // 必须指定为 form
        ],
        
        'form' => [
            [
                'type' => 'password',
                'name' => 'old_password',
                'label' => '原密码',
                'required' => true,
                'placeholder' => '请输入原密码'
            ],
            [
                'type' => 'password',
                'name' => 'new_password',
                'label' => '新密码',
                'required' => true,
                'placeholder' => '6-20位，包含字母和数字'
            ],
            [
                'type' => 'password',
                'name' => 'confirm_password',
                'label' => '确认密码',
                'required' => true,
                'placeholder' => '再次输入新密码'
            ]
        ],
        
        'api' => [
            'submit' => '/api/admin/change-password'
        ]
    ];
}
```

#### 纯表单页面特点

- ✅ 没有表格，只有表单
- ✅ 没有新增/编辑弹窗，直接在页面显示表单
- ✅ 适合单次操作（修改密码、系统设置等）

---

## 📚 总结

### 开发流程

1. 创建数据表
2. 创建控制器（4 个方法）
3. 注册路由（4 个路由）
4. 添加配置（1 个方法）
5. 添加菜单（1 行 HTML）

**总计：不超过 100 行代码完成完整 CRUD！**

### 核心优势

- ✅ **零手写**：配置即代码
- ✅ **AI 友好**：结构化配置
- ✅ **超轻量**：核心代码 < 500 行
- ✅ **高复用**：一套渲染器通用
- ✅ **易维护**：修改配置即可

---

## 🚀 下一步

- 查看 `app/config/CrudConfig.php` 学习现有配置
- 查看 `public/admin/assets/js/crud-renderer.js` 了解渲染逻辑
- 参考用户管理示例添加新模块

**开发愉快！**
