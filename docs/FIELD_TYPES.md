# 字段类型参考手册

> **Flight Base 2.2 支持 16+ 字段类型**

本文档列出所有支持的字段类型及其配置参数。

---

## 📋 字段类型列表

### 1. input - 文本输入框

**基础文本输入**

```php
[
    'type' => 'input',
    'name' => 'title',
    'label' => '标题',
    'inputType' => 'text',        // text | email | url | tel
    'placeholder' => '请输入标题',
    'required' => true,           // 全局必填
    'required_on_add' => true,    // 仅新增必填
    'disabled_on_edit' => true,   // 编辑时禁用
    'verify' => 'required',
    'tip' => '不超过50个字符'
]
```

**参数说明**：
- `inputType`: 输入类型（text, email, url, tel）
- `placeholder`: 占位提示文字
- `required`: 是否必填（true / false / 'add'）
- `required_on_add/edit`: 细粒度必填控制
- `disabled_on_add/edit`: 细粒度禁用控制
- `hidden_on_add/edit`: 细粒度隐藏控制
- `verify`: Layui 验证规则
- `tip`: 字段提示信息

---

### 2. password - 密码输入框

**密码输入（自动隐藏）**

```php
[
    'type' => 'password',
    'name' => 'password',
    'label' => '密码',
    'placeholder' => '留空则不修改',
    'required' => 'add',          // 仅新增时必填
    'tip' => '密码长度至少6位'
]
```

---

### 3. textarea - 多行文本框

**多行文本输入**

```php
[
    'type' => 'textarea',
    'name' => 'description',
    'label' => '描述',
    'rows' => 5,                  // 行数
    'placeholder' => '请输入描述',
    'required' => true
]
```

---

### 4. editor - 富文本编辑器

**富文本编辑（可视化编辑）**

```php
[
    'type' => 'editor',
    'name' => 'content',
    'label' => '内容',
    'height' => '400px',          // 编辑器高度
    'required' => true,
    'uploadImage' => true,        // 是否支持上传图片（默认 true）
    'uploadUrl' => '/api/admin/upload',  // 图片上传接口
    'placeholder' => '请输入内容...',
    'tip' => '支持富文本编辑'
]
```

**说明**：
- 弹窗宽度自动调整为 90%
- 使用 **wangEditor**（国产、轻量、中文友好）
- 支持图片上传（需配置 `uploadUrl`）
- 完整的富文本功能：标题、加粗、斜体、颜色、列表、表格等
- 自动保存到隐藏的 textarea，提交表单时自动获取内容

---

### 5. number - 数字输入框

**数字输入（支持小数、范围）**

```php
[
    'type' => 'number',
    'name' => 'price',
    'label' => '价格',
    'min' => 0,                   // 最小值
    'max' => 99999,               // 最大值
    'step' => 0.01,               // 步进值
    'placeholder' => '请输入价格',
    'required' => true
]
```

---

### 6. radio - 单选框

**单选框组**

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

**动态数据源**：

```php
[
    'type' => 'radio',
    'name' => 'role_id',
    'label' => '角色',
    'url' => '/api/admin/roles',     // 动态获取
    'valueField' => 'id',
    'labelField' => 'name'
]
```

**参数说明**：
- `options`: 静态选项数组
- `url`: 动态数据接口
- `valueField`: 值字段名
- `labelField`: 显示字段名
- `value`: 选项值
- `title`: 显示文本
- `checked`: 是否默认选中

---

### 7. select - 下拉框

**静态选项**：

```php
[
    'type' => 'select',
    'name' => 'category',
    'label' => '分类',
    'required' => true,
    'options' => [
        ['value' => 1, 'label' => '科技'],
        ['value' => 2, 'label' => '生活']
    ]
]
```

**动态数据源（v2.3.1 新增）**：

```php
[
    'type' => 'select',
    'name' => 'category_id',
    'label' => '分类',
    'url' => '/api/admin/categories', // 数据接口
    'valueField' => 'id',             // 选项值字段（默认 id）
    'labelField' => 'name'            // 选项显示字段（默认 name）
]
```

**接口返回格式要求**：
```json
{
    "code": 0,
    "data": [
        {"id": 1, "name": "分类A"},
        {"id": 2, "name": "分类B"}
    ]
}
```
或者
```json
{
    "code": 0,
    "data": {
        "list": [ ... ]
    }
}
```

---

### 8. switch - 开关

**开关按钮（布尔值）**

```php
[
    'type' => 'switch',
    'name' => 'is_published',
    'label' => '是否发布',
    'text' => '发布|草稿',        // ON|OFF 文本
    'checkedValue' => 1,          // 选中时的值
    'tip' => '开启后立即生效'
]
```

---

### 9. date - 日期选择器

**日期选择**

```php
[
    'type' => 'date',
    'name' => 'publish_date',
    'label' => '发布日期',
    'format' => 'yyyy-MM-dd',     // 日期格式
    'placeholder' => '请选择日期',
    'required' => true
]
```

---

### 10. datetime - 日期时间选择器

**日期 + 时间选择**

```php
[
    'type' => 'datetime',
    'name' => 'created_at',
    'label' => '创建时间',
    'format' => 'yyyy-MM-dd HH:mm:ss',
    'placeholder' => '请选择日期时间'
]
```

---

### 11. time - 时间选择器

**时间选择**

```php
[
    'type' => 'time',
    'name' => 'open_time',
    'label' => '营业时间',
    'format' => 'HH:mm:ss',
    'placeholder' => '请选择时间'
]
```

---

### 11.5. timestamp - 时间戳 (v2.3.2 新增)

**自动转换时间戳**

```php
[
    'type' => 'timestamp',
    'name' => 'expire_at',
    'label' => '过期时间',
    'required' => true
]
```

**说明**：
- 数据库存储为 10 位整数时间戳（INT）
- 前端显示为格式化日期时间（`yyyy-MM-dd HH:mm:ss`）
- 提交时自动转换回时间戳
- 完美解决数据库存储时间戳但需要前端可视化编辑的问题

---

### 12. upload - 文件上传

**通用文件上传**

```php
[
    'type' => 'upload',
    'name' => 'attachment',
    'label' => '附件',
    'uploadUrl' => '/api/admin/upload',  // 上传接口
    'tip' => '支持 PDF、Word、Excel，大小不超过 10MB'
]
```

**说明**：
- 上传成功后返回文件 URL
- 需要后端提供上传接口（已内置 `/api/admin/upload`）

---

### 13. image - 图片上传

**图片上传（带预览）**

```php
[
    'type' => 'image',
    'name' => 'cover',
    'label' => '封面图',
    'uploadUrl' => '/api/admin/upload',
    'tip' => '建议尺寸：800x600，格式：JPG/PNG，大小不超过 5MB'
]
```

**说明**：
- 自动显示图片预览（最大 200x200px）
- 弹窗宽度自动调整为 900px

---

### 14. color - 颜色选择器

**颜色选择**

```php
[
    'type' => 'color',
    'name' => 'theme_color',
    'label' => '主题色',
    'default' => '#409eff',       // 默认颜色
    'placeholder' => '请选择颜色'
]
```

**说明**：
- 返回十六进制颜色值（如 `#409eff`）
- 需要 Layui 的 colorpicker 模块

---

### 15. slider - 滑块

**数值滑块**

```php
[
    'type' => 'slider',
    'name' => 'volume',
    'label' => '音量',
    'min' => 0,                   // 最小值
    'max' => 100,                 // 最大值
    'default' => 50,              // 默认值
    'tip' => '拖动滑块调整数值'
]
```

**说明**：
- 适合数值范围选择
- 需要 Layui 的 slider 模块

---

### 18. rate - 评分组件

**评分打分**

```php
[
    'type' => 'rate',
    'name' => 'score',
    'label' => '评分',
    'length' => 5,                // 星星总数（默认5）
    'theme' => '#FFB800',         // 颜色
    'default' => 3
]
```

---

### 19. icon - 图标选择器

**图标选择（支持搜索）**

```php
[
    'type' => 'icon',
    'name' => 'icon',
    'label' => '图标',
    'placeholder' => '点击选择图标'
]
```

---

### 20. tags - 标签输入

**多标签输入**

```php
[
    'type' => 'tags',
    'name' => 'keywords',
    'label' => '关键词',
    'placeholder' => '输入后回车或逗号分隔'
]
```
427
428---
429
430### 21. permissions - 权限配置（v2.2.0 新增）

**权限多选配置**

```php
[
    'type' => 'permissions',
    'name' => 'permissions',
    'label' => '权限配置',
    'required' => false,
    'hidden_on_super_admin' => true  // 超级管理员隐藏此字段
]
```

**显示效果**：

```
权限配置
┌─────────────────────────────┐
│ 📦 用户管理                  │
│   ☑ 查看列表  ☑ 新增        │
│   ☑ 编辑     ☐ 删除         │
├─────────────────────────────┤
│ 📦 文章管理                  │
│   ☑ 查看列表  ☑ 新增        │
│   ☑ 编辑     ☑ 删除         │
└─────────────────────────────┘
```

**存储格式**（JSON）：

```json
{
    "users": ["list", "create", "update"],
    "articles": ["list", "create", "update", "delete"]
}
```

**说明**：
- 自动从 `CrudConfig::getMenus()` 获取所有模块
- 每个模块默认有 4 个操作：list、create、update、delete
- 权限配置接口：`config.api.permissions` 或 `/api/admin/permissions`
- 勾选后自动保存为 JSON 格式
- 适用于管理员权限管理

**使用场景**：
- ✅ 管理员权限配置
- ✅ 角色权限配置

**详细文档**：[PERMISSIONS.md](PERMISSIONS.md)

---

### 17. actions - 操作列配置（v2.3.0 新增）

**自定义行操作按钮**

在 `CrudConfig` 的 `actions` 数组中配置：

```php
'actions' => [
    [
        'text' => '编辑',
        'action' => 'edit',
        'icon' => 'layui-icon-edit',
        'class' => 'layui-btn-normal',
        'permission' => 'update'
    ],
    [
        'text' => '预览',
        'action' => 'preview',
        'type' => 'iframe',
        'url' => '/article/{id}',
        'width' => '1000px',
        'height' => '80%'
    ]
]
```

**说明**：
- 替代旧版 `toolbar` 列配置
- 支持 `type: 'iframe'` (弹窗) 和 `type: 'page'` (跳转)
- 支持 `permission` 字段进行权限控制
- 支持 `{id}` 等变量替换

---

## 🎨 完整示例（商品管理）

```php
<?php
// app/config/CrudConfig.php
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

---

## 📊 表格列类型配置

除了标准的 `field` 和 `title`，`CrudConfig` 的 `table.cols` 还支持以下特殊 `type`：

### 1. datetime - 日期时间
自动将时间戳或日期字符串格式化为 `yyyy-MM-dd HH:mm:ss`。

```php
['field' => 'created_at', 'title' => '创建时间', 'type' => 'datetime', 'width' => 180]
```

### 2. image - 图片预览
显示图片缩略图，点击可预览大图。

```php
['field' => 'cover', 'title' => '封面', 'type' => 'image', 'width' => 100]
```

### 3. file - 文件链接 (v2.3.2)
显示“查看文件”链接，点击在新标签页打开。

```php
['field' => 'attachment', 'title' => '附件', 'type' => 'file', 'width' => 120]
```

### 4. switch - 状态开关
显示为 Layui 开关，支持点击即时切换状态（需配置 API）。

```php
['field' => 'status', 'title' => '状态', 'type' => 'switch', 'text' => '启用|禁用']
```

### 5. select - 数据映射
将数据库存储的值（如 1/0）映射为显示文本（如 正常/禁用）。

```php
['field' => 'type', 'title' => '类型', 'type' => 'select', 'options' => [
    ['value' => 1, 'label' => '普通'],
    ['value' => 2, 'label' => 'VIP']
]]
```

### 6. tags - 标签展示 (v2.3.3)
将逗号分隔的字符串（如 "php,java"）渲染为多个蓝色徽章（Badge）。

```php
['field' => 'tags', 'title' => '标签', 'type' => 'tags']
```

### 7. icon - 图标展示 (v2.3.3)
渲染 Layui 图标。

```php
['field' => 'icon', 'title' => '图标', 'type' => 'icon', 'width' => 60]
```

### 8. link - 超链接 (v2.3.3)
渲染为自定义超链接。

```php
['field' => 'url', 'title' => '链接', 'type' => 'link', 'text' => '访问官网']
```

### 9. progress - 进度条 (v2.3.3)
渲染为进度条。

```php
['field' => 'percent', 'title' => '进度', 'type' => 'progress', 'theme' => '#5FB878']
```

### 10. rate - 评分 (v2.3.3)
渲染为评分组件（星星）。

```php
['field' => 'score', 'title' => '评分', 'type' => 'rate', 'length' => 5, 'theme' => '#FFB800']
```

---

## 💡 使用技巧

### 1. 条件必填与细粒度控制

**基础必填**：
```php
// 仅新增时必填
'required' => 'add'

// 始终必填
'required' => true

// 非必填
'required' => false
```

**细粒度控制 (v2.3.2)**：
```php
'required_on_add' => true,    // 新增必填
'required_on_edit' => true,   // 编辑必填
'hidden_on_add' => true,      // 新增隐藏
'hidden_on_edit' => true,     // 编辑隐藏
'disabled_on_add' => true,    // 新增禁用
'disabled_on_edit' => true    // 编辑禁用
```

---

### 2. 默认值

```php
'default' => '默认值'
```

---

### 3. 字段提示

```php
'tip' => '这是一条提示信息，显示在输入框下方'
```

---

### 4. 自定义验证

在 `crud-renderer.js` 的 `registerFormValidators()` 方法中添加：

```javascript
form.verify({
    price: function(value) {
        if (value <= 0) {
            return '价格必须大于0';
        }
    }
});
```

---

## 🔧 扩展自定义字段类型

在 `public/admin/assets/js/crud-renderer.js` 的 `renderFormFields()` 方法中添加新的 case：

```javascript
case 'my-custom-type':
    html += `<div class="my-custom-field">
                <!-- 自定义 HTML -->
             </div>`;
    break;
```

---

## 📚 相关文档

- [ADMIN_DEV.md](ADMIN_DEV.md) - 后台开发完整指南
- [README.md](README.md) - 快速开始
- [ARCHITECTURE.md](ARCHITECTURE.md) - 架构说明

---

**字段类型参考完毕！开发愉快！** 🚀
