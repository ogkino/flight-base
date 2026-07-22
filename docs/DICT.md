# 数据字典（Dict）使用文档

Flight Base 内置了一套通用的**数据字典**功能，用于集中管理系统中各种"下拉选项/状态枚举"，
例如通用状态（启用/禁用）、性别、订单状态等。字典管理是框架的**默认基础能力**，随
`example_db.sql` 一起安装，位于后台「系统管理」分组下。

## 一、设计模型

字典采用经典的"类型 + 数据"两级结构：

| 表 | 说明 |
|---|---|
| `og_dict_type` | 字典类型（分类），如 `user_status`、`gender` |
| `og_dict_data` | 字典数据项（label/value 对），归属于某个 `type` |

```
og_dict_type                       og_dict_data
┌────┬────────┬──────────────┐     ┌────┬───────────────┬──────┬───────┐
│ id │ name   │ type         │     │ id │ type          │label │ value │
├────┼────────┼──────────────┤     ├────┼───────────────┼──────┼───────┤
│ 1  │ 用户性别│ gender       │ ──▶ │ 3  │ gender        │ 未知 │ 0     │
│ 2  │ 通用状态│ common_status│     │ 4  │ gender        │ 男   │ 1     │
└────┴────────┴──────────────┘     │ 5  │ gender        │ 女   │ 2     │
                                    └────┴───────────────┴──────┴───────┘
```

- `og_dict_type.type` 是字典的**唯一编码**（英文，字母开头，字母/数字/下划线），代码里通过它读取字典，
  创建后不可修改（后台表单已禁用编辑），避免已引用该编码的代码/数据失效。
- `og_dict_data.value` 是实际存储的值（字符串比较），`label` 是显示文本。
- `is_default` 用于标记某个类型下的默认项（同一类型下只能有一个，后台会自动互斥切换）。
- `color` 是可选的前端展示色值（如状态标签颜色），业务代码可自行使用。

## 二、后台管理入口

后台「系统管理」分组下只有**一个菜单**：「字典管理」（`dictType`，标准配置驱动 CRUD，见
`app/config/CrudConfig.php` 的 `dictType()`），列表展示所有字典类型。

字典数据（label/value 数据项）**不再是独立菜单**——如果把所有字典类型的数据放在一张表里，
字典一多就会混在一起很难找到自己要改的那个类型。因此改为：在「字典管理」列表的每一行，
点击「配置数据」按钮，以弹层方式打开该字典类型专属的数据管理面板：

```
字典管理（列表）
┌────┬──────────┬────────────────┬──────┬──────────────────────┐
│ ID │ 字典名称  │ 类型编码        │ 状态  │ 操作                  │
├────┼──────────┼────────────────┼──────┼──────────────────────┤
│ 1  │ 用户性别  │ gender         │ 启用  │ [配置数据] [编辑] [删除] │  ← 点击"配置数据"
└────┴──────────┴────────────────┴──────┴──────────────────────┘
                                              ↓ 弹层打开，只显示 gender 这一个类型的数据
                              ┌─────────────────────────────────────┐
                              │ 用户性别  gender          [新增数据] │
                              │ ┌───┬──────┬──────┬────┬────────┐  │
                              │ │ID │标签  │键值  │... │操作     │  │
                              │ ├───┼──────┼──────┼────┼────────┤  │
                              │ │3  │未知  │0     │... │编辑/删除│  │
                              │ │4  │男    │1     │... │编辑/删除│  │
                              │ │5  │女    │2     │... │编辑/删除│  │
                              │ └───┴──────┴──────┴────┴────────┘  │
                              └─────────────────────────────────────┘
```

实现方式：「配置数据」是 `dictType()` 配置里的一个 `type => 'iframe'` 行操作
（`url` 为 `/admin/dict-data-manage?type={type}`），会把当前行的 `type` 编码带过去。
弹层页面本身是一个手写的 PHP + Layui 页面（`app/views/admin/dict-data-manage.php`，
由 `DictDataController::managePage()` 加载，走 Cookie 鉴权，风格上等价于 `view-admin` 技能里的
"自定义视图"，只是不走 `/admin/view/{viewName}` 那套独立视图权限模型，而是复用
`dictType` 权限），内部的增删改仍然调用标准的 `/api/admin/dict/data/*` 接口。

删除字典类型会级联删除该类型下所有字典数据。

## 三、业务代码如何使用字典

框架提供 `app/helpers/dict.php` 中的全局函数（已通过 composer `files` 自动加载，无需 `require`）：

```php
// 获取某个字典类型下所有启用的数据项（按 sort 升序），每项包含 id/type/label/value/sort/color/is_default/remark
dict(string $type): array

// 根据 type + value 反查显示文本（label），找不到时返回 $default
dictLabel(string $type, $value, string $default = ''): string

// 获取某个字典类型的默认项（is_default=1），未设置时返回 null
dictDefault(string $type): ?array
```

### 示例 1：在 Controller 里格式化列表返回值

```php
foreach ($orders as &$order) {
    $order['status_text'] = dictLabel('order_status', $order['status'], '未知状态');
}
```

### 示例 2：在 `CrudConfig.php` 里把字典转换成 `select`/`switch` 的静态 `options`

CrudConfig 的方法本身就是普通 PHP 代码，可以直接调用 `dict()` 拼装 `options`，
生成的选项会随字典后台的修改自动更新（每次请求都会重新查询，不会因为缓存而"改了不生效"）：

```php
[
    'type' => 'select',
    'name' => 'status',
    'label' => '状态',
    'options' => array_map(function ($item) {
        return ['value' => $item['value'], 'label' => $item['label'], 'color' => $item['color']];
    }, dict('common_status')),
],
```

### 示例 3：让字段直接"动态"关联某个字典类型（无需改 CrudConfig 就能增删选项）

如果希望字典数据变化后**不需要重新发布代码**就能反映到下拉框，使用 `url` 动态数据源，
指向内置的字典数据只读接口 `/api/admin/dict/data/options?type=xxx`：

```php
[
    'type' => 'select',
    'name' => 'status',
    'label' => '状态',
    'url' => '/api/admin/dict/data/options?type=common_status',
    'valueField' => 'value',
    'labelField' => 'label',
],
```

两种方式如何选？

| 方式 | 适用场景 |
|---|---|
| `array_map(..., dict($type))` 生成静态 `options` | 选项基本固定，希望表格里也能用 `options` 直接映射颜色/文案（无需额外请求） |
| `'url' => '/api/admin/dict/data/options?type=xxx'` | 选项经常变动，希望字典改了立刻生效，无需改代码 |

## 四、内置的字典管理接口一览

| 方法 | 路径 | 说明 |
|---|---|---|
| GET | `/api/admin/dict/types` | 字典类型列表（分页，供 CRUD 表格用） |
| GET | `/api/admin/dict/types/options` | 全部启用的字典类型（不分页，供下拉选择字典类型用） |
| POST | `/api/admin/dict/type` | 新增字典类型 |
| POST | `/api/admin/dict/type/{id}` | 更新字典类型 |
| DELETE | `/api/admin/dict/type/{id}` | 删除字典类型（级联删除其下所有字典数据） |
| GET | `/api/admin/dict/data` | 字典数据列表（分页，支持 `?type=xxx&keyword=xxx` 筛选） |
| GET | `/api/admin/dict/data/options` | 指定 `?type=xxx` 下全部启用的数据项（不分页，供业务表单动态下拉用） |
| POST | `/api/admin/dict/data` | 新增字典数据 |
| POST | `/api/admin/dict/data/{id}` | 更新字典数据 |
| DELETE | `/api/admin/dict/data/{id}` | 删除字典数据 |
| GET | `/admin/dict-data-manage?type=xxx` | 「配置数据」弹层页面（非 JSON 接口，返回 HTML），由字典管理列表的行操作打开 |

以上 `/api/admin/*` 接口均需管理员登录（`AuthMiddleware::checkAdmin()`），并受权限系统保护。

**关于权限维度**：字典类型和字典数据**共用同一个权限模块 `dictType`**（`list/create/update/delete`），
不单独存在 `dictData` 权限。这是因为管理入口已经合并成一个页面——如果按"菜单项 = 权限模块"的
机械对应关系拆成两项，权限配置面板里会出现两行，但后台实际只有一个入口，容易让配置权限的人困惑，
而且"只能看、不能改"这种精细化需求本来就能靠同一个模块内的 4 个动作勾选实现（比如只给
`list`，不给 `create/update/delete`），并不需要额外拆一个模块。

如果以后确实需要把"字典分类的增删改"和"字典数据的增删改"完全分开授权（例如只允许某些管理员
往已有分类里加数据、但不能新建/删除分类本身），可以参考 `getAllPermissions()` 里其他模块的写法，
重新引入一个独立的 `dictData` 权限项，并把 `DictDataController` 里的 `checkPermission('dictType', ...)`
改回 `checkPermission('dictData', ...)`。

## 五、Worker 模式下的缓存说明

`dict()` 使用 `Flight::set()/Flight::get()` 做"请求级"缓存：同一次 HTTP 请求内多次调用同一个
`$type` 不会重复查库；`public/worker.php` 会在每次请求开始前清空该缓存（`dictCache`），
保证 FrankenPHP Worker 模式下不会把上一个请求缓存的字典数据"泄漏"给下一个请求
（例如管理员刚编辑了字典，下一个请求应立即读到新数据）。

**如果你要给字典加更长效的缓存（如 Redis/APCu）**，请自行控制失效时机（在
`DictDataController::create/update/delete` 里主动清除对应 `type` 的缓存），不要使用 PHP
`static` 变量做跨请求缓存 —— 在 Worker 模式下会导致数据在进程重启前"永久"不更新。

## 六、与多语言（i18n）的关系

「字典管理」列表页（菜单名、表格列名、表单标签等）已按 `_en` 后缀约定支持中英文，
详见 `docs/I18N.md`。但「配置数据」弹层页面（`app/views/admin/dict-data-manage.php`）
是手写的自定义 PHP/JS 页面，目前**只有中文**，未接入 `lang()`；如需支持多语言，
按 `docs/I18N.md` 里 "View 模式多语言" 的约定，在 PHP 里用 `lang('dict_data_manage.xxx')`
输出文案即可。

字典数据本身的 `label`（如"启用"/"禁用"）目前是单一文本，
不自动跟随后台语言切换。如果业务需要多语言字典项，可以：

1. 在 `og_dict_data` 表中增加 `label_en` 等列，`DictDataController` 和后台表单相应增加字段；
2. 在 `dict()`/`dictLabel()` 内部按 `i18nCurrentFieldSuffix()` 解析对应语言的 label 列
   （实现方式可参考 `app/helpers/i18n.php` 里 `i18nApplyFieldSuffix()` 的思路）。

当前版本暂未内置这一层，按需扩展即可。
