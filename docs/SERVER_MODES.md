# 运行模式说明与切换指南

> 适用版本：Flight Base v2.5+

Flight Base 支持三种运行模式，同一套代码、无需任何修改，只需切换服务器配置即可：

| 模式 | 入口文件 | 适用场景 |
|------|---------|---------|
| **PHP-FPM** | `public/index.php` | 传统 Nginx/Apache + PHP-FPM |
| **FrankenPHP Classic** | `public/index.php` | FrankenPHP 无状态模式，直接替代 FPM |
| **FrankenPHP Worker** | `public/worker.php` | FrankenPHP 长驻进程，最高性能 |

---

## 架构说明

```
public/index.php   ──┐
                     ├──→  app/bootstrap.php（初始化 + 路由，所有模式共享）
public/worker.php  ──┘
```

- `app/bootstrap.php`：包含所有初始化逻辑（DB 注册、路由定义、中间件挂载），是所有模式的共同基础。
- `public/index.php`：PHP-FPM / Classic 模式入口，每次请求执行完整 PHP 生命周期。
- `public/worker.php`：Worker 模式入口，bootstrap.php 只执行一次，循环处理多个请求。

---

## FrankenPHP 实际运行指南

### Caddyfile 放在哪里

**放在项目根目录**，与 `public/`、`app/` 同级：

```
flight_base/          ← 项目根目录
├── Caddyfile         ← 放这里（FrankenPHP 默认从当前目录找这个文件）
├── Caddyfile.dev     ← 可选：本地开发专用配置
├── app/
├── public/
│   ├── index.php
│   └── worker.php
└── ...
```

> **原因**：FrankenPHP（Caddy）运行时默认加载当前工作目录下的 `Caddyfile`。
> 你也可以把它放在任意位置，用 `--config` 参数指定路径。

---

### 运行命令

#### Linux / macOS

```bash
# 进入项目根目录
cd /path/to/flight_base

# 启动（自动读取当前目录的 Caddyfile）
frankenphp run

# 指定 Caddyfile 路径启动
frankenphp run --config /path/to/Caddyfile

# 热重载配置（不停服，修改 Caddyfile 后使用）
frankenphp reload

# 后台运行（守护进程模式）
frankenphp start

# 停止后台运行的进程
frankenphp stop
```

#### Windows

```powershell
# 进入项目根目录
cd C:\Users\12112\Desktop\flight_base

# 启动（自动读取当前目录的 Caddyfile）
.\frankenphp.exe run

# 指定 Caddyfile 路径
.\frankenphp.exe run --config Caddyfile

# 热重载（另开一个终端执行）
.\frankenphp.exe reload
```

> Windows 下 `start` / `stop` 守护进程模式支持有限，建议生产环境使用 Linux 或 Docker。

---

### 本地开发配置（推荐）

本地开发不需要真实域名，使用 `localhost` 加端口即可。
在项目根目录创建 `Caddyfile.dev`：

```caddyfile
{
    frankenphp
    # 关闭自动 HTTPS（本地开发不需要证书）
    auto_https off
}

# 监听 8080 端口，访问 http://localhost:8080
:8080 {
    root * ./public

    # Classic 模式（开发推荐，日志清晰）
    php_server

    file_server
}
```

启动命令：

```bash
# Linux / macOS
frankenphp run --config Caddyfile.dev

# Windows
.\frankenphp.exe run --config Caddyfile.dev
```

访问 `http://localhost:8080`，后台管理访问 `http://localhost:8080/admin/login.html`。

> **为什么开发用 Classic 而不是 Worker？**
> Classic 模式每次请求都重新加载代码，修改 PHP 文件后**无需重启**即可生效，开发体验更好。
> Worker 模式修改代码后需要重启才能生效（进程长驻内存）。

---

### 生产环境配置

在项目根目录创建 `Caddyfile`（正式环境）：

```caddyfile
{
    frankenphp

    # （可选）配置邮箱用于 Let's Encrypt 证书通知
    email admin@your-domain.com
}

# Caddy 自动申请并续期 HTTPS 证书
your-domain.com {
    root * /var/www/flight_base/public

    # Worker 模式（生产推荐，最高性能）
    # ⚠️ worker 路径必须是绝对路径，不能使用相对路径
    php_server {
        worker /var/www/flight_base/public/worker.php

        # Worker 进程数，默认 = CPU 核心数 × 2，可按需调整
        # num_threads 8
    }

    file_server

    # 禁止直接访问敏感目录（安全加固）
    @denied path /app/* /vendor/* /.env /composer.json /composer.lock
    respond @denied 404
}
```

启动：

```bash
cd /var/www/flight_base
frankenphp run
```

> **自动 HTTPS 前提**：域名已解析到服务器 IP，服务器 80/443 端口开放。
> FrankenPHP 会自动完成证书申请，无需任何额外操作。

---

### 生产环境 systemd 服务（Linux 长期运行）

创建服务文件 `/etc/systemd/system/frankenphp.service`：

```ini
[Unit]
Description=FrankenPHP - Flight Base
After=network.target

[Service]
Type=notify
User=www-data
Group=www-data
WorkingDirectory=/var/www/flight_base
ExecStart=/usr/local/bin/frankenphp run --config /var/www/flight_base/Caddyfile
ExecReload=/usr/local/bin/frankenphp reload --config /var/www/flight_base/Caddyfile
TimeoutStopSec=5s
LimitNOFILE=1048576
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

启用并启动：

```bash
# 重新加载 systemd 配置
systemctl daemon-reload

# 开机自启
systemctl enable frankenphp

# 启动服务
systemctl start frankenphp

# 查看状态
systemctl status frankenphp

# 查看日志
journalctl -u frankenphp -f

# 修改 Caddyfile 后热重载（不中断请求）
systemctl reload frankenphp
```

---

### Docker 运行

```dockerfile
# Dockerfile
FROM dunglas/frankenphp

# 复制项目代码
COPY . /app

# 设置工作目录为 public（FrankenPHP 官方镜像的默认根目录）
WORKDIR /app

# 启动命令（使用项目根目录的 Caddyfile）
CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
```

`docker-compose.yml` 示例：

```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/app
      - ./public/uploads:/app/public/uploads
    environment:
      - SERVER_NAME=your-domain.com
    restart: unless-stopped
```

启动：

```bash
docker compose up -d
```

---

### Worker 性能调优

通过 `num_threads` 控制 Worker 进程数：

```caddyfile
php_server {
    worker /path/to/flight_base/public/worker.php   # 必须使用绝对路径
    num_threads 16   # 推荐值：CPU 核心数 × 2
}
```

| 服务器规格 | 推荐 num_threads |
|-----------|----------------|
| 2 核 | 4 |
| 4 核 | 8 |
| 8 核 | 16 |
| 16 核 | 32 |

> 实际最优值需要压测，从 `CPU核心数 × 2` 开始调整。

---

## 模式一：PHP-FPM（传统模式）

### Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;

    root /path/to/flight_base/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### Apache 配置（需启用 mod_rewrite）

在 `public/` 目录创建 `.htaccess`：

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

### 特点

- ✅ 每次请求独立 PHP 进程，完全无状态
- ✅ 无需任何改造，传统部署方式
- ✅ 进程崩溃不影响其他请求
- ❌ 每次请求都要重新加载代码、重建 DB 连接，性能相对较低

---

## 模式二：FrankenPHP Classic 模式

### 安装 FrankenPHP

```bash
# 下载二进制（Linux）
curl -fsSL https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-x86_64 \
  -o /usr/local/bin/frankenphp && chmod +x /usr/local/bin/frankenphp

# 或使用 Docker
docker pull dunglas/frankenphp
```

### Caddyfile 配置

```caddyfile
{
    frankenphp
}

your-domain.com {
    root * /path/to/flight_base/public

    # Classic 模式：自动处理 URL rewrite，等同于 nginx try_files
    php_server

    file_server
}
```

### 特点

- ✅ 代码与 PHP-FPM 模式完全一致，使用同一个 `index.php` 入口
- ✅ FrankenPHP 内置 Web 服务器，无需单独安装 Nginx
- ✅ 支持 HTTP/2、HTTP/3、自动 HTTPS（Let's Encrypt）
- ✅ 比 PHP-FPM 启动更快（Go 实现）
- ❌ 与 PHP-FPM 一样，每次请求重新执行 PHP 代码

### 与 PHP-FPM 的区别

对于 Classic 模式，FrankenPHP 和 PHP-FPM 对代码来说**完全透明**，无任何区别。你可以将 FrankenPHP Classic 理解为「更快的 PHP-FPM 替代品」。

---

## 模式三：FrankenPHP Worker 模式

### Caddyfile 配置

```caddyfile
{
    frankenphp
}

your-domain.com {
    root * /path/to/flight_base/public

    # Worker 模式：指定 worker 入口脚本（必须使用绝对路径）
    php_server {
        worker /path/to/flight_base/public/worker.php
    }

    file_server
}
```

> **切换提示**：Worker 和 Classic 的区别只有一行：
> - Classic：`php_server`
> - Worker：`php_server { worker /path/to/flight_base/public/worker.php }`
>
> ⚠️ **注意**：`worker` 的路径必须是**绝对路径**，相对路径会导致 `The system cannot find the file specified` 错误。

### Docker 示例

```dockerfile
FROM dunglas/frankenphp

COPY . /app
WORKDIR /app/public

CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
```

### 特点

- ✅ PHP 进程长驻内存，`bootstrap.php` 只执行一次
- ✅ DB 连接全程复用，省去每次请求重新连接的开销
- ✅ 路由表只注册一次，减少重复 require 开销
- ✅ 性能比 PHP-FPM 高 3~10 倍（视业务复杂度）
- ⚠️ 有状态限制，详见下方「开发注意事项」

---

## 三种模式性能对比

| 对比项 | PHP-FPM | Classic | Worker |
|--------|---------|---------|--------|
| 每次请求是否重新加载代码 | ✅ 是 | ✅ 是 | ❌ 否（只加载一次）|
| 每次请求是否重建 DB 连接 | ✅ 是 | ✅ 是 | ❌ 否（连接复用）|
| 相对性能 | 基准 | 略高于 FPM | 高 3~10 倍 |
| 配置复杂度 | 低 | 低 | 低 |
| 代码改动 | 无 | 无 | 无 |

---

## 开发注意事项

### ⚠️ 注意 1：Worker 模式下 PHP 全局状态会跨请求持久

这是 Worker 模式最重要的限制。

**框架已做的处理（无需开发者操心）：**

`worker.php` 在每次请求开始前，会自动清除 Flight 内存储的用户认证状态：

```php
Flight::set('currentUser', null);
Flight::set('userType', null);
Flight::set('admin_id', null);
```

**你需要注意的是：** 如果你在控制器或帮助函数中使用了 `static` 变量缓存数据，在 Worker 模式下这些缓存会跨请求持久，且永不失效（直到 worker 进程重启）。

```php
// ⚠️ 这在 Worker 模式下是"永久缓存"，谨慎使用
function getSomeData() {
    static $data = null;
    if ($data === null) {
        $data = db()->select('some_table', '*');
    }
    return $data;
}
```

框架内置的 `config()` 函数使用了 `static` 缓存配置文件，这是故意的——配置文件不会在运行时变化，缓存是合理的。但如果你缓存的是数据库查询结果，就要注意数据过期问题。

### ⚠️ 注意 2：不能直接使用 `exit` 或 `die`

在 Worker 模式下，`exit` / `die` 会**杀死 worker 进程**（FrankenPHP 会自动重启，但严重影响性能）。

**正确做法**：使用框架提供的 `terminateRequest()` 函数替代 `exit`：

```php
// ❌ 错误：直接 exit
function myAction() {
    echo json_encode(['ok' => true]);
    exit;  // Worker 模式下会杀死 worker 进程！
}

// ✅ 正确：使用 terminateRequest()
function myAction() {
    echo json_encode(['ok' => true]);
    terminateRequest();  // Worker 模式抛异常，FPM/Classic 模式直接 exit
}
```

框架已内置的 `success()`、`error()`、`layuiTable()` 函数内部均已正确调用 `terminateRequest()`，无需手动处理。

**只有当你在控制器里自己手写输出并终止请求时，才需要注意此点。**

### ⚠️ 注意 3：Worker 模式下 DB 连接是长连接

Medoo（PDO）的连接在 Worker 模式下跨请求持久复用。优点是性能好，潜在问题是：

- **MySQL 会话超时**：MySQL 默认 `wait_timeout=28800`（8小时）。如果 Worker 进程空闲超过这个时间，再次请求时 DB 连接会失效，导致报错。
- **解决方案**：在 MySQL 配置中适当增大 `wait_timeout`，或者在生产环境配置连接健康检查。对于活跃的 Web 服务，通常不会有问题。

### ⚠️ 注意 4：Session 使用方式

框架的主要认证使用 JWT Token（HTTP Header 或 Cookie），不依赖 PHP Session。

Session 仅用于 CSRF Token 功能（`generateCsrfToken()` / `validateCsrfToken()`）。

- **FPM/Classic 模式**：Session 会在 CSRF 函数被调用时惰性启动（安全）。
- **Worker 模式**：`worker.php` 在每次请求开始时主动调用 `session_start()`，确保 Session 在每次请求都正确初始化。

如果你在自己的控制器里使用 Session，**不需要手动调用 `session_start()`**，框架已处理好。

### ✅ 注意 5：新增路由只需修改 bootstrap.php

无论哪种模式，路由统一在 `app/bootstrap.php` 中定义。新增模块按原有方式操作：

```php
// app/bootstrap.php
Flight::route('GET /api/admin/your-module', function () {
    \App\Api\Admin\YourController::list();
});
```

Worker 模式下路由只注册一次（进程启动时），对性能有额外收益。

### ✅ 注意 6：`terminateRequest()` 之后的代码不会执行

`terminateRequest()` 的行为与 `exit` 一致——之后的代码都不会执行：

```php
// FPM/Classic：exit 后直接结束
// Worker：抛出异常后被捕获，当前请求处理链终止
success(['id' => $newId], '创建成功');
// 下面这行永远不会执行：
doSomeCleanup();  // ← 不会执行
```

如果有需要在响应发出后执行的清理逻辑（比如记录日志），请在 `success()`/`error()` **之前**执行，或者考虑使用 `register_shutdown_function()`。

---

## 快速切换参考

### 常用命令速查

```bash
frankenphp run                        # 启动（前台，读取 ./Caddyfile）
frankenphp run --config Caddyfile.dev # 启动（指定配置文件）
frankenphp start                      # 启动（后台守护进程）
frankenphp stop                       # 停止后台进程
frankenphp reload                     # 热重载配置（不中断请求）
```

### 从 PHP-FPM 切换到 FrankenPHP Classic

1. 安装 FrankenPHP
2. 在项目根目录创建 `Caddyfile`（内容见上方「Classic 模式」章节）
3. 停止 Nginx + PHP-FPM，启动 FrankenPHP：`frankenphp run`
4. 代码零改动 ✅

### 从 FrankenPHP Classic 切换到 Worker 模式

只需修改 `Caddyfile` 一行，然后重启：

```diff
- php_server
+ php_server {
+     worker /path/to/flight_base/public/worker.php
+ }
```

> ⚠️ `worker` 路径必须写**绝对路径**，例如：
> - Linux：`/var/www/flight_base/public/worker.php`
> - Windows：`D:\phpstudy_pro\WWW\flight_base\public\worker.php`

```bash
# 重启使配置生效
frankenphp stop && frankenphp run
# 或热重载（生产环境推荐，不中断流量）
frankenphp reload
```

代码零改动 ✅

### 从 Worker 模式回退到 Classic / PHP-FPM

```diff
- php_server {
-     worker /path/to/flight_base/public/worker.php
- }
+ php_server
```

```bash
frankenphp reload
```

---

## 文件变更记录

本次为支持多模式运行所做的改动：

| 文件 | 变更类型 | 说明 |
|------|---------|------|
| `app/bootstrap.php` | 新建 | 从 `index.php` 抽取的共享初始化 + 路由代码 |
| `public/index.php` | 修改 | 精简为 2 行（require bootstrap + Flight::start）|
| `public/worker.php` | 新建 | FrankenPHP Worker 模式专用入口 |
| `app/Exceptions/RequestTerminatedException.php` | 新建 | Worker 模式请求终止异常类 |
| `app/helpers/functions.php` | 修改 | 新增 `terminateRequest()`，替换 3 处 `exit` |
| `app/helpers/permission.php` | 修改 | 2 处 `exit` 替换为 `terminateRequest()` |
| `app/api/admin/AdminViewController.php` | 修改 | 1 处 `exit` 替换为 `terminateRequest()` |
| `app/helpers/security.php` | 修改 | 移除顶层 `session_start()`（避免 Worker 模式只启动一次）|
