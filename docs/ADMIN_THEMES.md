# 管理后台多皮肤（Theme）开发说明

> 面向维护 Flight Base 管理端的开发者。  
> 机制：不同 `login_{theme}.html` 选型 → 写入主题状态 → 统一进入 `index.html` → 叠加 `admin.theme.{theme}.css`，并由 **菜单范式层** 决定侧栏几何形态。

---

## 1. 设计原则

| 原则 | 说明 |
|------|------|
| 默认皮不变 | 未配置时，`login.html` + `index.html` + `admin.css` 仍是官方原始皮 |
| 部署可指定默认主题 | 在 `AdminThemeConfig::DEFAULT_THEME` 写主题 id；空 = 原始皮 |
| 壳页唯一 | 登录成功只进 `index.html`，**禁止**再复制 `index_xxx.html` |
| 叠加非替换 | 主题 CSS 叠在 `admin.css` 之上；结构/断点优先改 `admin.css` |
| 选型靠入口 | `login_{id}.html` 仍可随时切换主题；与部署默认主题并存 |
| 菜单形态独立 | 颜色跟主题走，**圆角/左边条/胶囊等几何**跟 `data-menu-layout` 走 |

---

## 1.1 部署默认主题（`DEFAULT_THEME`）

配置文件：`app/config/AdminThemeConfig.php`

```php
/** 空字符串 = 原始皮；例如 'ocean' / 'teal' / 'wechat' = 部署默认用该主题 */
public const DEFAULT_THEME = '';
```

### 如何生效（改完不会「只刷 index」就变）

浏览器里还记着上次的 `admin_theme` Cookie / localStorage，**优先级高于**配置默认值。按下面做：

1. 修改 `DEFAULT_THEME`（例如改成 `'wechat'`）并保存  
2. **打开或刷新** `/admin/login.html`（主入口）  
   - 非空时会自动跳到 `login_{主题}.html`  
3. 登录进后台，侧栏即该主题 + 对应菜单范式  

若仍是旧皮 / 新部署仍显示原始登录页：

1. F12 → **Network**，看 `theme-config`（路径 `../api/admin/theme-config`，**无 .js 后缀**）是否 **200**  
   - 正常内容类似：`window.ADMIN_DEFAULT_THEME="semi";...`  
   - 若是 **404** 且请求的是 `theme-config.js`：Linux Nginx 常把 `*.js` 当静态文件找磁盘，找不到就直接 404、不进 PHP。请改用无后缀路由 `/api/admin/theme-config`（仓库已改）  
   - 若无后缀仍 **404**：检查站点伪静态 / Web 根是否为 `public`，以及 `/api/*` 是否转发到 `index.php`  
   - 若是 **500**：看 PHP/Worker 日志  
2. Console 若出现 `[theme] theme-config 未加载`，说明前端没拿到 `DEFAULT_THEME`，会当成 `default` 留在 `login.html`  
3. Application → Local Storage → 删掉 `admin_theme`、`admin_login_entry`、`admin_default_theme_token` 后再开 `login.html`  
4. FrankenPHP Worker：改 PHP 常量后需**重启 worker**（进程常驻会缓存旧类）  
5. 也可直接打开 `/admin/login_semi.html` 验证皮肤文件是否存在

对比验证菜单差异（推荐）：

| 打开 | 菜单范式 | 应看到 |
|------|----------|--------|
| `login.html`（DEFAULT 为空） | `pill` | 右侧大圆角选中条 |
| `login_dark.html` | `edge` | 直角 + 粗左边条，选中不铺满实心 |
| `login_ocean.html` | `capsule` | 两侧留白的全圆角胶囊 |
| `login_wechat.html` | `cell` | 通栏分割线列表 + 左边条 |
| `login_notion.html` | `minimal` | 很矮很淡的行 |

页面 `<html>` 上可检查：`data-admin-theme`、`data-menu-layout`；Network 里应有 `admin.menu-layouts.css`。

### 与 `login_xxx` 选型的关系

| 配置值 | 行为 |
|--------|------|
| `''`（空） | 原始皮；`login.html` 不跳转 |
| `'ocean'` 等 | 主入口跟随该主题；`login_dark.html` 等仍可临时切换 |

**优先级（高 → 低）：**

1. `login_{id}.html` 的 `setAdminTheme(id, …)`  
2. 已有 localStorage / Cookie（且入口不是被部署默认重置的主入口）  
3. `DEFAULT_THEME`（经 `theme-config.js`）  
4. 原始皮 `default`

`theme-config.js` 已设 `Cache-Control: no-store`，并带 `ADMIN_DEFAULT_THEME_TOKEN`：当你改了 `DEFAULT_THEME` 且当前入口是 `login.html` 时，下次进壳页会自动跟上新默认；**不会**打断正在用的 `login_xxx` 选型。

---

## 2. 关键文件

| 路径 | 职责 |
|------|------|
| `public/admin/assets/js/theme.js` | 读写主题、挂载主题 CSS、挂载菜单范式 CSS、主题→范式映射 |
| `public/admin/assets/css/admin.css` | 壳层骨架（布局、组件、默认侧栏） |
| `public/admin/assets/css/admin.menu-layouts.css` | 菜单范式（几何）；**必须在主题 CSS 之后加载** |
| `public/admin/assets/css/admin.theme.{id}.css` | 单主题覆盖层（变量、配色、登录页结构样式） |
| `public/admin/login.html` / `login_{id}.html` | 登录入口；`login.html` 调用 `applyPrimaryLoginTheme()` |
| `public/admin/index.html` | 统一壳页；先引 `theme-config.js`，再 `applyAdminThemeCss()` |
| `app/config/AdminThemeConfig.php` | **`DEFAULT_THEME`**、规范化、菜单映射、输出 theme-config.js |
| `GET /api/admin/theme-config` | 注入 `window.ADMIN_DEFAULT_THEME`（无鉴权；勿用 `.js` 后缀） |
| `app/views/admin/_head.php` | View：Cookie 优先，否则 `resolvedDefaultTheme()` |

**缓存版本**：改主题/范式 CSS 后，同步 bump：

- `theme.js` 内 `THEME_CSS_VERSION` / `MENU_LAYOUT_CSS_VERSION`
- 登录页 link 上的 `?v=`
- `_head.php` 里的 `?v=`
- `index.html` 里 `theme.js?v=`

---

## 3. 运行时数据流

```
login_ocean.html
  ├─ 引入 admin.css + admin.theme.ocean.css
  ├─ setAdminTheme('ocean', 'login_ocean.html')
  │     → localStorage + Cookie：admin_theme / admin_login_entry
  │     → html[data-menu-layout="capsule"]
  └─ 登录成功 → index.html

index.html
  ├─ admin.css
  └─ applyAdminThemeCss()
        ├─ html[data-admin-theme="ocean"]
        ├─ html[data-menu-layout="capsule"]
        ├─ 追加 admin.theme.ocean.css（非 default）
        └─ 再追加 admin.menu-layouts.css   ← 顺序重要

View iframe（_head.php）
  └─ Cookie admin_theme（若无则 DEFAULT_THEME）→ 同主题 CSS + menu-layouts + data-* 属性

login.html（主入口）
  ├─ theme-config.js → ADMIN_DEFAULT_THEME
  └─ applyPrimaryLoginTheme()
        ├─ default → setAdminTheme('default', 'login.html')
        └─ 其它 id → location.replace('login_{id}.html')
```

存储约定：

| Key | 位置 | 说明 |
|-----|------|------|
| `admin_theme` | localStorage + Cookie | 主题 ID，仅 `[a-z0-9_-]`；`default` = 不挂主题文件 |
| `admin_login_entry` | localStorage + Cookie | 退出 / 鉴权失败跳转的登录页文件名 |

`common.js` 的 `logout()` / 鉴权失败会读 `getAdminLoginEntry()`。

---

## 4. 菜单范式（Menu Layout）

壳层菜单 DOM 固定为 Layui `ul.layui-nav-tree`（所有主题共用）。  
仅靠主题换色时侧栏会「长得差不多」——因此增加独立范式层。

### 4.1 属性与文件

- `html[data-menu-layout="{layout}"]`：由 `theme.js` / `_head.php` 写入
- 样式：`admin.menu-layouts.css`
- 职责划分：
  - **范式 CSS**：圆角、边距、行高、左边条宽度、分组标题大小写等几何
  - **主题 CSS**：`--brand` / `--sidebar`、选中背景色、字色、图标色等

### 4.2 内置范式

| layout | 几何特征 | 典型仿制 |
|--------|----------|----------|
| `pill` | 右侧大圆角选中条 + 大写分组 | Flight Base 默认 |
| `fill` | 矩形实心选中块 | Ant Pro、Teal、Semi、AdminLTE |
| `edge` | 左侧指示条、直角项 | Dark、Element、Arco、Carbon、Cyber |
| `capsule` | 全圆角胶囊项 | Ocean、Naive、Tabler |
| `cell` | 通栏列表 + 底部分割线 | WeChat |
| `block` | 浅色软色块、小圆角 | TDesign、Fluent |
| `rail` | 更高行高 + 大圆角（导航轨感） | Material 3 |
| `minimal` | 紧凑弱选中 | Notion |
| `docs` | 大写分组 + 圆角实心 | Bootstrap Docs |

### 4.3 主题 → 范式映射

**唯一源：** `app/config/AdminThemeConfig.php` → `MENU_LAYOUT_BY_THEME`  
经 `GET /api/admin/theme-config.js` 注入为 `window.ADMIN_MENU_LAYOUT_BY_THEME`，`theme.js` 读取它。  
**不要再改** `theme.js` 里的兜底表（仅登录页未加载 theme-config 时用）。

改完后：**硬刷新 `index.html`**（F12 看 `<html data-menu-layout="...">` 是否已变）。  
Worker 模式改 PHP 后需重启进程。

当前绑定：

| 主题 | 范式 | 登录结构要点 |
|------|------|----------------|
| `default` | `pill` | 居中登录卡 |
| `teal` | `fill` | 左右分栏 |
| `dark` | `edge` | 控制台风 |
| `ocean` | `capsule` | Hero 堆叠 |
| `wechat` | `cell` | 灰白绿 cell |
| `bootstrap` | `docs` | Docs 风侧栏色 |
| `element` | `edge` | vue-element-admin 左边条 |
| `ant` | `fill` | 左右分栏品牌区 |
| `naive` | `capsule` | 顶色条浮卡 |
| `arco` | `edge` | 顶 Hero + 下沉卡 |
| `tdesign` | `block` | 顶栏 + 白卡 |
| `semi` | `fill` | 左说明右表单 |
| `tabler` | `capsule` | 双栏信息卡 |
| `adminlte` | `fill` | Logo 外置小盒子 |
| `material` | `rail` | 色带 Sheet |
| `carbon` | `edge` | 黑灰工业分栏 |
| `fluent` | `block` | 顶栏 + 磨砂卡 |
| `notion` | `minimal` | 极简无重卡 |
| `cyber` | `edge` | 终端窗框 |

未知主题 ID 回退：`fill`。

### 4.4 如何改某个主题的菜单形态

1. 只改 `AdminThemeConfig.php` 里该主题的 layout 值（例如 `'dark' => 'cell'`）  
2. 硬刷新后台壳页 `index.html`  
3. 确认 `<html data-menu-layout="cell">`；Network 里 `/api/admin/theme-config.js` 应含新映射  
4. 若颜色不对：只改 `admin.theme.{id}.css` 里选中/悬停的 background/color

### 4.5 如何新增一种范式

1. 在 `admin.menu-layouts.css` 增加 `html[data-menu-layout="xxx"] ...`  
2. 把某个主题映射到 `xxx`  
3. 更新本文档表格  

不要改菜单 HTML，除非要做顶栏导航等**结构级**方案（当前不支持，需改 `index.html` 渲染）。

---

## 5. 内置主题一览

| 主题 ID | 登录入口 | 风格来源 | 菜单范式 | 辨识点 |
|---------|----------|----------|----------|--------|
| `default` | `login.html` | Flight Base | `pill` | 官方蓝深侧栏 |
| `teal` | `login_teal.html` | 自研 | `fill` | 分栏 + 青绿填充 |
| `dark` | `login_dark.html` | 自研深色 | `edge` | 控制台 + 光条 |
| `ocean` | `login_ocean.html` | 自研海蓝 | `capsule` | Hero + 浅色胶囊 |
| `wechat` | `login_wechat.html` | 微信 | `cell` | 灰白绿 cell |
| `bootstrap` | `login_bootstrap.html` | Bootstrap 5 | `docs` | `#0d6efd` Docs 侧栏 |
| `element` | `login_element.html` | Element UI | `edge` | `#409EFF` 左边条 |
| `ant` | `login_ant.html` | Ant Design | `fill` | 分栏 + Pro 实心选中 |
| `naive` | `login_naive.html` | Naive UI | `capsule` | 顶色条 + 翠绿胶囊 |
| `arco` | `login_arco.html` | Arco Design | `edge` | Hero + 左边条 |
| `tdesign` | `login_tdesign.html` | TDesign | `block` | 顶栏 + 软色块 |
| `semi` | `login_semi.html` | Semi Design | `fill` | 左右说明区 + 填充 |
| `tabler` | `login_tabler.html` | Tabler | `capsule` | 双栏卡 + 胶囊 |
| `adminlte` | `login_adminlte.html` | AdminLTE | `fill` | 经典小盒子 |
| `material` | `login_material.html` | Material 3 | `rail` | Sheet + 导航轨感 |
| `carbon` | `login_carbon.html` | IBM Carbon | `edge` | 工业直角左边条 |
| `fluent` | `login_fluent.html` | Fluent 2 | `block` | 磨砂卡 + 浅色块 |
| `notion` | `login_notion.html` | Notion | `minimal` | 极简弱选中 |
| `cyber` | `login_cyber.html` | 赛博终端 | `edge` | 终端框 + 霓虹边 |

交付单一部署时，可只保留需要的 `login_*.html` 与对应 `admin.theme.*.css`，删掉未使用皮肤文件即可（记得同步改映射表或忽略未用 ID）。

---

## 6. 新增一套皮肤（Checklist）

### 6.1 起 ID

小写英文/数字/`_`/`-`，例如 `forest`。不要用 `default`。

### 6.2 主题 CSS

路径：`public/admin/assets/css/admin.theme.{id}.css`

建议顺序：

1. 文件头注释：主题名、登录入口、选用的 `data-menu-layout`
2. `:root` 变量：`--brand`、`--brand-soft`、`--menu-active-bg`、`--menu-active-color`、`--bg`、`--sidebar`、`--sidebar-text`、`--line`、`--radius`、`--sidebar-w` 等  
   - **必填** `--menu-active-bg` / `--menu-active-color`：侧栏选中色由 `admin.menu-layouts.css` 末尾读取，勿再依赖写死蓝色或弱选择器覆盖  
   - 实心选中（Ant / Teal / Tabler 等）：`bg: var(--brand); color: #fff`  
   - 浅底选中（Naive / TDesign / Material 等）：`bg: var(--brand-soft); color: var(--brand)`
3. 覆盖壳层：header / sidebar 底色与字色 / content / 卡片 / 按钮 / 表格 / 表单 / layer
4. **选中/悬停颜色**（几何交给范式层；颜色优先用上面两个变量）
5. 表单主题色：`.layui-form-onswitch` 等用 `var(--brand)`（`admin.css` 默认写死蓝色）
6. Toast：`.layui-layer-msg` 背景用 `var(--bg-elevated)`；成功图标 `.layui-icon-success` 用 `color: var(--brand)`（Layui 2.8，不是旧版 `ico1`）
7. 登录页 layout class（如 `.login-layout-forest`）的专属结构样式

### 6.3 登录页

路径：`public/admin/login_{id}.html`（可从现有登录页复制后改结构与文案）

必备：

```html
<link rel="stylesheet" href="assets/css/admin.css?v=...">
<link rel="stylesheet" href="assets/css/admin.theme.{id}.css?v=1.4.0">
<script>window.ADMIN_THEME_CSS_BASE = 'assets/css/';</script>
<script src="assets/js/theme.js?v=1.4.0"></script>
<script>setAdminTheme('{id}', 'login_{id}.html');</script>
```

登录成功：

```js
setAdminTheme('{id}', 'login_{id}.html');
location.href = 'index.html'; // 必须进统一壳页
```

登录区 HTML 可自由设计（分栏、Hero、终端框等），表单字段与登录 API 保持一致即可。

### 6.4 注册菜单范式

在 `AdminThemeConfig.php` 的 `MENU_LAYOUT_BY_THEME` 中增加一行，例如：

```php
'forest' => 'fill',
```

硬刷新 `index.html` 即可（不必再改 `theme.js`）。

### 6.5 自检

- [ ] 打开 `login_{id}.html`，登录页本身已是新皮  
- [ ] 登录后 `html` 上有正确的 `data-admin-theme` 与 `data-menu-layout`  
- [ ] Network 中可见 `admin.theme.{id}.css`，且其后有 `admin.menu-layouts.css`  
- [ ] 侧栏选中形态符合所选范式（不是所有主题都像默认 pill）  
- [ ] 任意 View iframe 页配色与壳一致  
- [ ] 退出回到 `login_{id}.html`  
- [ ] 再用 `login.html` 登录，回到 default（无主题 CSS，`data-menu-layout=pill`）

---

## 7. 不要做的事

- ❌ 复制整份 `index.html` 做 `index_xxx.html`
- ❌ 把某一皮肤硬编码进 `admin.css`
- ❌ 在主题 CSS 里用更低优先级规则死磕 `border-radius` 对抗范式层（应改映射或改 `admin.menu-layouts.css`）
- ❌ 主题 ID 含路径、点号等特殊字符
- ❌ 依赖已删除的 `tools/generate-admin-themes.php` / `regen-theme-*.php`（皮肤文件已手写维护）

---

## 8. theme.js API

```js
setAdminTheme('teal', 'login_teal.html'); // 写入 LS + Cookie，并应用 data-menu-layout
getAdminTheme();                          // 当前主题 id（含部署默认回退）
getConfiguredDefaultTheme();              // AdminThemeConfig::DEFAULT_THEME 解析结果
getAdminLoginEntry();                     // 退出应回的登录页
getMenuLayout();                          // 当前菜单范式 id
applyAdminThemeCss();                     // 挂载/移除主题 CSS + 菜单范式 CSS
applyPrimaryLoginTheme();                 // 仅 login.html：原始皮 or 跳转 login_{id}.html
applyMenuLayout('ocean');                 // 仅刷新 data-menu-layout（一般不必手调）
```

可选：

```js
window.ADMIN_THEME_CSS_BASE = 'assets/css/'; // 登录页相对路径；壳/_head 默认 /admin/assets/css/
window.MENU_LAYOUT_BY_THEME;                 // 可运行时查看映射（改源码才持久）
```

---

## 9. 与默认文件的关系

| 文件 | 改动边界 |
|------|----------|
| `login.html` | 引入 `theme-config.js` + `applyPrimaryLoginTheme()` |
| `index.html` | 引入 `theme-config.js` + `applyAdminThemeCss()` |
| `admin.css` | 框架升级主样式；不堆单皮肤定制 |
| `admin.menu-layouts.css` | 跨主题的菜单几何；新增范式写这里 |
| `AdminThemeConfig.php` | **`DEFAULT_THEME` + `MENU_LAYOUT_BY_THEME` 唯一配置源** |
| `theme.js` / `_head.php` | 框架能力；新皮肤通常只改 PHP 映射一行 |

---

## 10. 排查速查

| 现象 | 排查 |
|------|------|
| 换主题侧栏几何仍像默认 | 是否加载了 `admin.menu-layouts.css`？`data-menu-layout` 是否正确？缓存是否旧？ |
| 范式对了但颜色不对 | 查 `admin.theme.{id}.css` 选中态 background/color |
| iframe 与壳不一致 | Cookie `admin_theme` 是否写入；`_head.php` 是否挂了同一 CSS |
| 退出跳错登录页 | `admin_login_entry` 是否在登录时写入 |
| 部署默认主题不生效 | 查 `AdminThemeConfig::DEFAULT_THEME`；Network 是否有 `/api/admin/theme-config.js`；是否仍被旧 Cookie 覆盖（清 `admin_theme` 再试） |
| `login.html` 未跳到指定皮 | `DEFAULT_THEME` 是否非空；`applyPrimaryLoginTheme` 是否在 `theme-config.js` 之后执行 |
| 原始皮被污染 | 是否误把定制写进 `admin.css`；`DEFAULT_THEME` 是否误填 |
