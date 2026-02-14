# 服务器配置指南

Flight 框架需要配置 URL 重写（伪静态）才能正常使用路由功能。

## Nginx 配置

### 方式一：标准配置（推荐）

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # 网站根目录指向 public 文件夹
    root /path/to/flight_base/public;
    index index.php index.html;
    
    # 字符集
    charset utf-8;
    
    # 访问日志
    access_log /var/log/nginx/flight_base_access.log;
    error_log /var/log/nginx/flight_base_error.log;
    
    # URL 重写规则（核心）
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;  # 或 unix:/var/run/php-fpm.sock
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
    
    # 拒绝访问隐藏文件
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        access_log off;
    }
}
```

### 方式二：phpstudy Nginx 配置

在 phpstudy 的站点配置中添加：

```nginx
server {
    listen 80;
    server_name flight-base.test;
    
    root "d:/phpstudy_pro/WWW/other/flight_base/public";
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php(.*)$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param  PATH_INFO  $fastcgi_path_info;
        fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
        include        fastcgi_params;
    }
}
```

### 重启 Nginx

```bash
# Linux
sudo nginx -t          # 测试配置
sudo nginx -s reload   # 重载配置

# phpstudy
# 在 phpstudy 界面点击"重启" Nginx
```

---

## Apache 配置

### 方式一：虚拟主机配置

编辑 Apache 配置文件（`httpd.conf` 或 `httpd-vhosts.conf`）：

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot "/path/to/flight_base/public"
    
    <Directory "/path/to/flight_base/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # URL 重写（如果 .htaccess 不生效）
        <IfModule mod_rewrite.c>
            RewriteEngine On
            
            # 如果请求的是真实文件或目录，直接访问
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            
            # 否则转发到 index.php
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    ErrorLog "logs/flight_base_error.log"
    CustomLog "logs/flight_base_access.log" common
</VirtualHost>
```

### 方式二：使用 .htaccess（已包含）

项目已经包含了 `.htaccess` 文件：

**public/.htaccess**

```apache
# Apache 重写规则
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # 如果请求的是真实文件或目录，直接访问
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # 否则转发到 index.php
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

### 启用 mod_rewrite 模块

确保 Apache 已启用 `mod_rewrite` 模块：

```bash
# Linux
sudo a2enmod rewrite
sudo systemctl restart apache2

# 或手动编辑 httpd.conf，取消注释：
LoadModule rewrite_module modules/mod_rewrite.so
```

### phpstudy Apache 配置

1. 打开 phpstudy
2. 站点管理 → 创建站点
3. 根目录填写：`d:\phpstudy_pro\WWW\other\flight_base\public`
4. phpstudy 会自动配置伪静态，不需要手动设置

---

## Windows hosts 配置

如果使用本地域名（如 `flight-base.test`），需要配置 hosts：

**文件位置**：`C:\Windows\System32\drivers\etc\hosts`

添加：
```
127.0.0.1  flight-base.test
```

**Linux/Mac**：`/etc/hosts`

---

## 测试配置是否生效

### 1. 访问健康检查接口

```bash
curl http://your-domain.com/api/health
```

**正确返回**：
```json
{
  "code": 0,
  "msg": "System is healthy",
  "data": {
    "status": "healthy",
    "database": "ok"
  }
}
```

**错误返回**（404 或找不到文件）：说明伪静态没有配置成功

### 2. 浏览器测试

访问：`http://your-domain.com/api/health`

- ✅ **成功**：返回 JSON 数据
- ❌ **失败**：404 错误或显示 "File not found"

### 3. 检查后台登录页

访问：`http://your-domain.com/admin/login.html`

- ✅ **成功**：显示登录页面
- ❌ **失败**：404 错误

---

## 常见问题

### 问题 1：访问 API 返回 404

**原因**：伪静态没有配置或没有生效

**解决方案**：
1. 检查 nginx 或 apache 配置中的 `try_files` 或 `RewriteRule`
2. 确保 `AllowOverride All`（Apache）
3. 重启 Web 服务器

### 问题 2：只有首页能访问，API 都是 404

**原因**：URL 重写规则没有生效

**Nginx 解决方案**：
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Apache 解决方案**：
检查 `.htaccess` 是否存在于 `public/` 目录

### 问题 3：Apache 不支持 .htaccess

**原因**：`AllowOverride` 设置为 `None`

**解决方案**：
在 Apache 配置中设置：
```apache
<Directory "/path/to/flight_base/public">
    AllowOverride All
</Directory>
```

### 问题 4：nginx 提示 "Primary script unknown"

**原因**：fastcgi_param 配置错误

**解决方案**：
```nginx
fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
```

确保 `$document_root` 指向正确的路径。

---

## phpstudy 快速配置（推荐）

### 步骤 1：创建站点

1. 打开 phpstudy
2. 点击"网站" → "创建网站"
3. 填写配置：
   - **域名**：`flight-base.test`
   - **根目录**：`d:\phpstudy_pro\WWW\other\flight_base\public`
   - **PHP 版本**：7.4 或 8.x
4. 点击"确定"

### 步骤 2：配置 hosts

1. 打开 `C:\Windows\System32\drivers\etc\hosts`
2. 添加：`127.0.0.1  flight-base.test`
3. 保存（需要管理员权限）

### 步骤 3：测试

访问：`http://flight-base.test/api/health`

如果返回 JSON 数据，配置成功！

---

## 安全建议

### 1. 禁止访问敏感目录

**Nginx**：
```nginx
location ~ ^/(runtime|app|vendor|composer\.json|\.env) {
    deny all;
    return 404;
}
```

**Apache**：已在根目录 `.htaccess` 中配置

### 2. 防止目录浏览

**Nginx**：
```nginx
autoindex off;
```

**Apache**：
```apache
Options -Indexes
```

### 3. 限制 PHP 执行目录

只允许在 `public/` 目录执行 PHP：

**php.ini**：
```ini
open_basedir = /path/to/flight_base/public:/path/to/flight_base/runtime
```

---

## 完整的 Nginx 生产环境配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # HTTP 跳转到 HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    # SSL 证书
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    root /path/to/flight_base/public;
    index index.php index.html;
    
    charset utf-8;
    
    # 日志
    access_log /var/log/nginx/flight_base_access.log;
    error_log /var/log/nginx/flight_base_error.log;
    
    # URL 重写
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP
    location ~ \.php$ {
        fastcgi_pass   unix:/var/run/php-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
    
    # 拒绝访问敏感文件
    location ~ ^/(\.env|\.git|composer\.(json|lock)|runtime) {
        deny all;
        return 404;
    }
    
    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    # Gzip 压缩
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
```

---

## 总结

**最简配置**（开发环境）：

1. **phpstudy 用户**：
   - 根目录指向 `public/`
   - phpstudy 会自动配置伪静态

2. **手动配置**：
   - Nginx：添加 `try_files $uri $uri/ /index.php?$query_string;`
   - Apache：确保 `.htaccess` 存在且 `AllowOverride All`

3. **测试**：
   - 访问 `http://your-domain.com/api/health`
   - 返回 JSON 即为成功

如有问题，请查看 Web 服务器错误日志。
