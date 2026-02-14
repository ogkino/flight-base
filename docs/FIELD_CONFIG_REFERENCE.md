# 字段配置快速参考

> **Flight Base 2.3 字段配置完整对照表**

---

## 🎯 配置格式对比

### 旧格式（向后兼容）

| 属性 | 值 | 说明 |
|------|---|------|
| `required` | `true` | 始终必填 |
| `required` | `'add'` | 只在新增时必填 |
| `required` | `'edit'` | 只在编辑时必填 |
| `required` | `false` | 非必填 |

### 新格式（推荐）

| 属性 | 值 | 说明 |
|------|---|------|
| `required_on_both` | `true` | 始终必填 |
| `required_on_add` | `true` | 只在新增时必填 |
| `required_on_edit` | `true` | 只在编辑时必填 |
| （不设置） | - | 非必填 |

---

## 📊 完整属性对照表

### 基础属性

| 属性 | 类型 | 必填 | 说明 | 示例 |
|------|------|------|------|------|
| `type` | string | ✅ | 字段类型 | `'input'`, `'select'`, `'editor'` |
| `name` | string | ✅ | 字段名（对应数据库字段） | `'username'`, `'email'` |
| `label` | string | ✅ | 字段标签 | `'用户名'`, `'邮箱地址'` |
| `placeholder` | string | ❌ | 输入提示 | `'请输入用户名'` |
| `tip` | string | ❌ | 字段说明 | `'4-20位字母、数字、下划线'` |
| `default` | mixed | ❌ | 默认值 | `''`, `0`, `1` |

### 必填控制

| 属性 | 值 | 新增 | 编辑 | 使用场景 |
|------|---|------|------|---------|
| `required_on_both` | `true` | ✅ 必填 | ✅ 必填 | 核心字段（标题、昵称） |
| `required_on_add` | `true` | ✅ 必填 | ❌ 非必填 | 密码、初始配置 |
| `required_on_edit` | `true` | ❌ 非必填 | ✅ 必填 | 拒绝原因、修改说明 |
| 不设置 | - | ❌ 非必填 | ❌ 非必填 | 可选字段（邮箱、电话） |

**旧格式对照**：
- `required: true` = `required_on_both: true`
- `required: 'add'` = `required_on_add: true`
- `required: 'edit'` = `required_on_edit: true`

### 显示/隐藏控制

| 属性 | 值 | 新增 | 编辑 | 使用场景 |
|------|---|------|------|---------|
| `hidden_on_add` | `true` | ❌ 隐藏 | ✅ 显示 | 最后修改时间、版本号 |
| `hidden_on_edit` | `true` | ✅ 显示 | ❌ 隐藏 | 邀请码、推荐人 |
| `hidden_on_super_admin` | `true` | - | 特殊 | ID=1 编辑时隐藏 |

### 禁用控制

| 属性 | 值 | 新增 | 编辑 | 使用场景 |
|------|---|------|------|---------|
| `disabled_on_add` | `true` | 🔒 禁用 | ✅ 可编辑 | 很少使用 |
| `disabled_on_edit` | `true` | ✅ 可编辑 | 🔒 禁用 | 用户名、唯一标识 |

---

## 🎨 典型场景配置

### 场景 1：用户名（不可修改）

```php
[
    'type' => 'input',
    'name' => 'username',
    'label' => '用户名',
    'required_on_both' => true,      // 始终必填
    'disabled_on_edit' => true       // 编辑时禁用
]
```

**效果**：
- ✅ 新增：可输入，必填
- 🔒 编辑：显示为灰色，不可修改

---

### 场景 2：密码（编辑时可选）

```php
[
    'type' => 'password',
    'name' => 'password',
    'label' => '密码',
    'required_on_add' => true,       // 只在新增时必填
    'tip' => '编辑时留空表示不修改密码'
]
```

**效果**：
- ✅ 新增：必填
- ❌ 编辑：非必填，留空表示不修改

---

### 场景 3：拒绝原因（审核场景）

```php
[
    'type' => 'textarea',
    'name' => 'reject_reason',
    'label' => '拒绝原因',
    'required_on_edit' => true,      // 只在编辑时必填
    'hidden_on_add' => true          // 新增时隐藏
]
```

**效果**：
- ❌ 新增：不显示
- ✅ 编辑：显示且必填

---

### 场景 4：最后修改人（只读信息）

```php
[
    'type' => 'input',
    'name' => 'last_modified_by',
    'label' => '最后修改人',
    'hidden_on_add' => true,         // 新增时隐藏
    'disabled_on_edit' => true       // 编辑时禁用
]
```

**效果**：
- ❌ 新增：不显示
- 🔒 编辑：显示但不可修改（只读）

---

### 场景 5：邀请码（只在创建时需要）

```php
[
    'type' => 'input',
    'name' => 'invite_code',
    'label' => '邀请码',
    'required_on_add' => true,       // 新增时必填
    'hidden_on_edit' => true         // 编辑时隐藏
]
```

**效果**：
- ✅ 新增：显示且必填
- ❌ 编辑：不显示

---

### 场景 6：超级管理员权限（特殊控制）

```php
[
    'type' => 'permissions',
    'name' => 'permissions',
    'label' => '权限配置',
    'hidden_on_super_admin' => true  // ID=1 编辑时隐藏
]
```

**效果**：
- 编辑普通管理员：显示
- 编辑 ID=1 管理员：隐藏（超级管理员固定拥有所有权限）

---

## 🔄 属性组合矩阵

### 必填 + 隐藏组合

| required_on_add | required_on_edit | hidden_on_add | hidden_on_edit | 新增 | 编辑 |
|----------------|------------------|---------------|----------------|------|------|
| ✅ | ❌ | ❌ | ❌ | 显示，必填 | 显示，非必填 |
| ❌ | ✅ | ✅ | ❌ | 不显示 | 显示，必填 |
| ✅ | ❌ | ❌ | ✅ | 显示，必填 | 不显示 |
| ✅ | ✅ | ❌ | ❌ | 显示，必填 | 显示，必填 |

### 必填 + 禁用组合

| required_on_both | disabled_on_edit | 新增 | 编辑 |
|-----------------|------------------|------|------|
| ✅ | ✅ | 可输入，必填 | 禁用（只读） |
| ✅ | ❌ | 可输入，必填 | 可输入，必填 |
| ❌ | ✅ | 可输入，非必填 | 禁用（只读） |

---

## 📝 完整示例：工单管理

```php
public static function tickets()
{
    return [
        'page' => [
            'title' => '工单管理',
            'icon' => 'layui-icon-note'
        ],
        
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
            
            // 优先级：新增必填
            [
                'type' => 'select',
                'name' => 'priority',
                'label' => '优先级',
                'required_on_add' => true,
                'options' => [
                    ['value' => 'low', 'label' => '低'],
                    ['value' => 'normal', 'label' => '普通'],
                    ['value' => 'high', 'label' => '高']
                ]
            ],
            
            // 拒绝原因：编辑必填，新增隐藏
            [
                'type' => 'textarea',
                'name' => 'reject_reason',
                'label' => '拒绝原因',
                'required_on_edit' => true,
                'hidden_on_add' => true,
                'placeholder' => '请输入拒绝原因'
            ],
            
            // 最后处理人：新增隐藏，编辑只读
            [
                'type' => 'input',
                'name' => 'last_handler',
                'label' => '最后处理人',
                'hidden_on_add' => true,
                'disabled_on_edit' => true
            ],
            
            // 邀请码：新增显示，编辑隐藏
            [
                'type' => 'input',
                'name' => 'invite_code',
                'label' => '邀请码',
                'hidden_on_edit' => true
            ]
        ]
    ];
}
```

---

## 🎯 快速决策树

**问题 1：这个字段是否必填？**

- 始终必填 → `required_on_both: true`
- 只在新增时必填 → `required_on_add: true`
- 只在编辑时必填 → `required_on_edit: true`
- 非必填 → 不设置 required

**问题 2：这个字段什么时候显示？**

- 始终显示 → 不设置 hidden
- 新增不显示，编辑显示 → `hidden_on_add: true`
- 新增显示，编辑不显示 → `hidden_on_edit: true`

**问题 3：这个字段能否修改？**

- 始终可修改 → 不设置 disabled
- 新增不可修改，编辑可修改 → `disabled_on_add: true`
- 新增可修改，编辑不可修改 → `disabled_on_edit: true`

---

## 🚀 迁移指南

### 从旧格式迁移到新格式

**步骤 1：找到所有 required 配置**

```bash
# 搜索旧格式
grep -r "required.*=>" app/config/
```

**步骤 2：替换为新格式**

```php
// 旧格式
'required' => true          →  'required_on_both' => true
'required' => 'add'         →  'required_on_add' => true
'required' => 'edit'        →  'required_on_edit' => true
'required' => false         →  （删除此行）
```

**步骤 3：测试**

1. 新增记录 - 验证必填规则
2. 编辑记录 - 验证必填规则
3. 字段显示/隐藏 - 验证显示规则

---

**注意**：旧格式仍然有效，无需立即迁移！新项目建议直接使用新格式。
