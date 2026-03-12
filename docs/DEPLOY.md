# 生产环境部署指南

本文档说明如何将 Flight Base 部署到生产环境。

## 部署前检查清单

### 1. 关闭调试模式 ⚠️

**非常重要**：生产环境必须关闭调试模式！

修改 `.env` 文件：
```ini
APP_DEBUG=false
```

或直接修改 `app/config/app.php`：
```php
'debug' => false,
```

**效果**：
- ✅ 隐藏错误详情，只返回 "服务器内部错误"
- ✅ 错误会记录到 `runtime/logs/` 目录
- ✅ 防止敏感信息泄露

### 2. 修改默认密码

默认管理员账号：
- 用户名：`admin`
- 密码：`password`

**必须修改**！登录后台后立即修改密码。

### 3. 配置数据库

修改 `.env` 文件：
```ini
DB_NAME=your_production_db
DB_USER=your_db_user
DB_PASS=your_strong_password
DB_PREFIX=og_
```

**建议**：使用强密码，不要使用 root 账号。

### 4. 配置 HTTPS

生产环境强烈建议使用 HTTPS。

#### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    # SSL 证书
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    root /path/to/flight_base/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # 拒绝访问敏感文件
    location ~ ^/(\.env|\.git|composer\.(json|lock)|runtime) {
        deny all;
        return 404;
    }
}
```

### 5. 设置文件权限（Linux）

```bash
# 设置所有者
chown -R www-data:www-data /path/to/flight_base

# 目录权限
find /path/to/flight_base -type d -exec chmod 755 {} \;

# 文件权限
find /path/to/flight_base -type f -exec chmod 644 {} \;

# 上传和日志目录需要可写
chmod -R 775 /path/to/flight_base/public/uploads
chmod -R 775 /path/to/flight_base/runtime/logs
```

### 6. 配置 PHP

**php.ini 建议配置**：

```ini
# 关闭错误显示
display_errors = Off
display_startup_errors = Off

# 记录错误到日志
log_errors = On
error_log = /var/log/php/error.log

# 上传限制
upload_max_filesize = 10M
post_max_size = 10M

# 执行时间限制
max_execution_time = 30

# 内存限制
memory_limit = 128M

# 时区
date.timezone = Asia/Shanghai

# OPcache 优化（推荐）
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

## 部署步骤

### 1. 上传代码

```bash
# 方式一：Git 部署（推荐）
cd /var/www
git clone https://github.com/your-repo/flight_base.git
cd flight_base

# 方式二：FTP/SFTP 上传
# 上传整个项目目录到服务器
```

### 2. 安装依赖

```bash
composer install --no-dev --optimize-autoloader
```

参数说明：
- `--no-dev`：不安装开发依赖
- `--optimize-autoloader`：优化自动加载

### 3. 配置环境变量

```bash
# 复制环境变量文件
cp ENV_TEMPLATE.txt .env

# 编辑配置
vi .env
```

修改为生产环境配置：
```ini
DB_NAME=production_db
DB_USER=db_user
DB_PASS=strong_password

APP_DEBUG=false
APP_NAME="Your App Name"

LOG_ENABLED=true
LOG_LEVEL=error
```

### 4. 导入数据库

```bash
mysql -u your_user -p your_database < database.sql
```

### 5. 设置权限

```bash
chmod -R 775 public/uploads
chmod -R 775 runtime/logs
```

### 6. 选择并配置运行模式

Flight Base 支持三种运行模式，按需选择：

| 模式 | 性能 | 适用场景 |
|------|------|---------|
| **PHP-FPM** | 基准 | 传统 Nginx + PHP-FPM，稳定可靠 |
| **FrankenPHP Classic** | 略高 | 无需 Nginx，一个二进制搞定 |
| **FrankenPHP Worker** | 高 3~10 倍 | 追求极致性能的生产环境 |

**PHP-FPM 模式**：参考 [SERVER_CONFIG.md](SERVER_CONFIG.md) 配置 Nginx 或 Apache。

**FrankenPHP 模式**：参考 [SERVER_MODES.md](SERVER_MODES.md) 配置 Caddyfile。

### 7. 测试部署

```bash
# 测试健康检查
curl https://your-domain.com/api/health

# 应该返回 JSON（不显示错误详情）
```

## 安全加固

### 1. 隐藏敏感目录

**Nginx**：
```nginx
location ~ ^/(runtime|app|vendor|composer\.json|\.env) {
    deny all;
    return 404;
}
```

**Apache**（`.htaccess`）：
```apache
<FilesMatch "^\.env|composer\.(json|lock)">
    Require all denied
</FilesMatch>

<DirectoryMatch "^/(runtime|app|vendor)">
    Require all denied
</DirectoryMatch>
```

### 2. 限制 PHP 执行目录

**php.ini**：
```ini
open_basedir = /path/to/flight_base/public:/path/to/flight_base/runtime
```

### 3. 配置防火墙

```bash
# UFW（Ubuntu）
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Firewalld（CentOS）
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 4. 启用速率限制

**Nginx 速率限制**：
```nginx
# 在 http 块中
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;

# 在 location 块中
location /api/ {
    limit_req zone=api_limit burst=20 nodelay;
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 监控和维护

### 1. 日志查看

```bash
# 查看错误日志
tail -f runtime/logs/$(date +%Y-%m-%d).log

# 查看 Web 服务器日志
tail -f /var/log/nginx/error.log

# 查看 PHP 错误日志
tail -f /var/log/php/error.log
```

### 2. 数据库备份

创建备份脚本 `backup.sh`：

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/flight_base"
DB_NAME="your_database"
DB_USER="your_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# 备份上传文件
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz -C /path/to/flight_base/public uploads

# 删除 7 天前的备份
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

设置定时任务：
```bash
crontab -e

# 每天凌晨 2 点备份
0 2 * * * /path/to/backup.sh >> /var/log/backup.log 2>&1
```

### 3. 性能优化

**OPcache 状态检查**：

创建 `public/opcache-status.php`（临时用）：
```php
<?php
// 仅限内网访问
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('Access denied');
}

phpinfo(INFO_GENERAL | INFO_MODULES);
```

访问后删除此文件！

### 4. 更新检查

定期检查依赖更新：
```bash
composer outdated
```

更新依赖（谨慎）：
```bash
composer update --no-dev
```

## 故障排查

### 问题 1：500 错误

**检查**：
1. 查看日志：`runtime/logs/$(date +%Y-%m-%d).log`
2. 查看 PHP 错误日志
3. 检查文件权限
4. 检查数据库连接

### 问题 2：性能问题

**优化**：
1. 启用 OPcache
2. 切换到 **FrankenPHP Worker 模式**（最简单最有效，详见 [SERVER_MODES.md](SERVER_MODES.md)）
3. 使用 Redis/Memcached 缓存
4. 优化数据库查询
5. 使用 CDN 加速静态资源
6. 启用 Gzip 压缩

### 问题 3：内存不足

**解决**：
1. 增加 PHP `memory_limit`
2. 优化代码，减少内存使用
3. 使用队列处理大任务

## 回滚方案

如果部署出现问题，快速回滚：

```bash
# Git 回滚
git reset --hard HEAD^
composer install --no-dev

# 恢复数据库
gunzip < /var/backups/flight_base/db_YYYYMMDD_HHMMSS.sql.gz | mysql -u user -p database
```

## 总结

**部署核心要点**：

✅ 关闭调试模式（`APP_DEBUG=false`）  
✅ 修改默认密码  
✅ 使用 HTTPS  
✅ 设置正确的文件权限  
✅ 配置数据库备份  
✅ 隐藏敏感文件  
✅ 定期查看日志  
✅ 按需选择运行模式（高性能推荐 FrankenPHP Worker，详见 [SERVER_MODES.md](SERVER_MODES.md)）

按照本文档操作，可以安全地将 Flight Base 部署到生产环境。
