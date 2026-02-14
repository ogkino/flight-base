# 三端分离部署指南

> **Flight Base v2.3 支持前后端完全分离部署**

本文档详细说明如何将 Flight Base 部署为三端分离架构，实现客户端、管理端、API 端的独立部署和扩展。

---

## 🎯 架构说明

### 单体部署 vs 分离部署

**单体部署（默认）**：
```
yoursite.com/
├── /                  # 前端首页
├── /admin/            # 管理后台
└── /api/              # API 接口
```

**三端分离部署**：
```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│  客户端 (C端)    │      │  管理端 (Admin)  │      │  API 服务器     │
│  c.xxx.com      │      │  admin.xxx.com  │      │  api.xxx.com    │
├─────────────────┤      ├─────────────────┤      ├─────────────────┤
│ Vue/React/小程序 │──┐   │ Layui 静态文件  │──┐   │ Flight + Medoo  │
│ 自建前端        │  │   │ (原 public/admin)│  │   │ (后端 PHP)      │
└─────────────────┘  │   └─────────────────┘  │   └─────────────────┘
                     │                          │
                     └──────────────────────────┴──────▶ 所有请求都发往 api.xxx.com
```

---

## ✅ 分离部署的优势

- ✅ **独立扩展**：三端可独立开发、部署、升级，互不影响
- ✅ **性能优化**：静态资源可使用 CDN 加速
- ✅ **负载均衡**：API 可部署多个节点，Nginx 负载均衡
- ✅ **安全隔离**：API 服务器可放在内网，减少攻击面
- ✅ **技术灵活**：客户端可用任何技术栈（Vue、React、小程序）
- ✅ **团队协作**：前后端团队可并行开发

---

## 📋 准备工作

### 1. 域名准备

申请或配置以下域名（示例）：
- `api.xxx.com` - API 服务器
- `admin.xxx.com` - 管理后台
- `c.xxx.com` 或 `www.xxx.com` - 客户端（可选）

### 2. SSL 证书（强烈推荐）

三端分离部署建议全站 HTTPS，保护 Token 安全。

---

## 🚀 部署步骤

### 第一步：部署 API 端 (api.xxx.com)

#### 1.1 上传后端代码

将以下目录上传到服务器：

```
/var/www/api.xxx.com/
├── app/                    # 应用核心
├── vendor/                 # Composer 依赖
├── runtime/                # 运行时目录
├── public/
│   ├── index.php           # 入口文件
│   ├── uploads/            # 上传文件目录
│   └── .htaccess
├── .env                    # 环境配置
└── composer.json
```

**注意**：不需要上传 `public/admin/` 目录（前端文件）！

#### 1.2 配置 CORS（重要！）

编辑 `app/config/cors.php`：

```php
<?php
return [
    'enabled' => true,
    'allowed_origins' => [
        'https://admin.xxx.com',    // 管理端域名
        'https://c.xxx.com',        // 客户端域名（如有）
        'https://www.xxx.com'       // 其他前端域名
    ],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'allow_credentials' => true
];
```

或者在 `.env` 中配置：

```ini
CORS_ENABLED=true
CORS_ALLOWED_ORIGINS=https://admin.xxx.com,https://c.xxx.com
```

#### 1.3 配置文件上传路径（重要！）

编辑 `app/api/admin/UploadController.php`，确保返回的文件 URL 包含完整域名：

```php
// 修改前
success([
    'url' => '/uploads/' . $filename
]);

// 修改后
success([
    'url' => 'https://api.xxx.com/uploads/' . $filename
]);
```

或者使用环境变量动态配置：

```php
// .env
APP_URL=https://api.xxx.com

// 控制器
success([
    'url' => env('APP_URL') . '/uploads/' . $filename
]);
```

#### 1.4 Nginx 配置

```nginx
server {
    listen 80;
    server_name api.xxx.com;
    root /var/www/api.xxx.com/public;
    index index.php;
    
    # 启用 CORS（如果代码层未处理）
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods 'GET, POST, PUT, DELETE, OPTIONS';
    add_header Access-Control-Allow-Headers 'Content-Type, Authorization';
    
    # URL 重写
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # 上传文件访问
    location /uploads/ {
        access_log off;
        expires 30d;
    }
    
    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }
}
```

#### 1.5 配置 HTTPS（推荐）

```bash
# 使用 Let's Encrypt 免费证书
sudo certbot --nginx -d api.xxx.com
```

---

### 第二步：部署管理端 (admin.xxx.com)

#### 2.1 上传前端代码

将 `public/admin/` 目录上传到服务器：

```
/var/www/admin.xxx.com/
└── public/
    ├── assets/
    │   ├── css/
    │   │   └── admin.css
    │   └── js/
    │       ├── config.js      # 👈 需要修改
    │       ├── common.js
    │       └── crud-renderer.js
    ├── index.html
    └── login.html
```

#### 2.2 修改 API 地址

编辑 `public/assets/js/config.js`：

```javascript
// 修改前（单体部署）
const API_BASE = window.location.origin;

// 修改后（分离部署）
const API_BASE = 'https://api.xxx.com';

// 管理后台 API 前缀（保持不变）
const ADMIN_API_PREFIX = '/api/admin';

// Token 存储键名（保持不变）
const TOKEN_KEY = 'admin_token';
```

#### 2.3 Nginx 配置（静态文件服务）

```nginx
server {
    listen 80;
    server_name admin.xxx.com;
    root /var/www/admin.xxx.com/public;
    index index.html;
    
    # 静态文件缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires 30d;
        access_log off;
    }
    
    # SPA 路由支持
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

#### 2.4 配置 HTTPS

```bash
sudo certbot --nginx -d admin.xxx.com
```

---

### 第三步：部署客户端 (c.xxx.com) - 可选

#### 3.1 自建前端（推荐）

使用 Vue、React、Next.js 等现代框架，通过 API 端获取数据。

**Vue 示例：**

```javascript
// src/api/request.js
import axios from 'axios';

const request = axios.create({
    baseURL: 'https://api.xxx.com',
    timeout: 10000
});

// 请求拦截器（自动添加 Token）
request.interceptors.request.use(config => {
    const token = localStorage.getItem('user_token');
    if (token) {
        config.headers.Authorization = token;
    }
    return config;
});

export default request;

// src/api/article.js
import request from './request';

export const getArticles = (params) => {
    return request.get('/api/articles', { params });
};

export const getArticleDetail = (id) => {
    return request.get(`/api/article/${id}`);
};
```

**React 示例：**

```javascript
// src/services/api.js
const API_BASE = 'https://api.xxx.com';

export const fetchArticles = async () => {
    const res = await fetch(`${API_BASE}/api/articles`, {
        headers: {
            'Authorization': localStorage.getItem('user_token')
        }
    });
    return res.json();
};
```

#### 3.2 使用 Views（可选）

如果您想继续使用 Flight 的 Views 功能，可以将 `app/views/` 和相关路由保留在 API 端：

```php
// public/index.php

// 前端页面路由（返回 HTML）
Flight::route('GET /', function(){
    \App\Api\ArticleController::listPage();
});

Flight::route('GET /article/@id', function($id){
    \App\Api\ArticleController::detailPage($id);
});
```

这样访问 `https://api.xxx.com/` 会看到首页，`https://api.xxx.com/article/1` 会看到文章详情。

---

## 🔧 常见问题

### 1. CORS 跨域问题

**现象**：浏览器控制台报错 `Access to fetch at 'https://api.xxx.com/api/...' from origin 'https://admin.xxx.com' has been blocked by CORS policy`

**解决方案**：

方案 A：修改 `app/config/cors.php`（推荐）：
```php
'allowed_origins' => ['https://admin.xxx.com', 'https://c.xxx.com']
```

方案 B：Nginx 配置（如果代码层未处理）：
```nginx
add_header Access-Control-Allow-Origin "https://admin.xxx.com";
add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
add_header Access-Control-Allow-Headers "Content-Type, Authorization";
add_header Access-Control-Allow-Credentials "true";
```

### 2. 图片无法显示

**现象**：上传的图片路径是 `/uploads/xxx.jpg`，但无法加载

**原因**：相对路径，浏览器会尝试访问 `https://admin.xxx.com/uploads/xxx.jpg`（错误）

**解决方案**：修改上传接口返回完整 URL：
```php
// app/api/admin/UploadController.php
success([
    'url' => 'https://api.xxx.com/uploads/' . $filename
]);
```

或者使用环境变量：
```php
success([
    'url' => env('APP_URL') . '/uploads/' . $filename
]);
```

### 3. 登录后跳转到 API 域名

**现象**：登录成功后，页面跳转到 `https://api.xxx.com/admin/index.html`

**原因**：前端代码中使用了相对路径跳转

**解决方案**：检查 `login.html` 和其他页面，确保跳转使用相对路径（不带域名）：
```javascript
// 正确
location.href = 'index.html';

// 错误
location.href = API_BASE + '/admin/index.html';
```

### 4. Token 失效问题

**现象**：频繁提示"未授权，请先登录"

**原因**：
1. Token 存储在 `localStorage` 中，不同域名的 `localStorage` 是隔离的
2. Cookie 跨域需要特殊配置

**解决方案**：Flight Base 使用 `localStorage` + JWT，天然支持跨域，无需额外配置。只要前端正确设置 `Authorization` Header 即可。

### 5. 开发环境跨域测试

**开发时本地测试跨域**：

修改 `app/config/cors.php`，临时允许所有域名：
```php
'allowed_origins' => ['*']  // 仅开发环境！生产必须指定域名
```

---

## 🔐 安全配置

### 1. HTTPS 强制跳转

**Nginx 配置**：
```nginx
server {
    listen 80;
    server_name api.xxx.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.xxx.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # ... 其他配置
}
```

### 2. 限制 CORS 来源

**生产环境必须明确指定允许的域名**：

```php
// app/config/cors.php
'allowed_origins' => [
    'https://admin.xxx.com',
    'https://www.xxx.com'
]
```

**切勿在生产环境使用 `'*'`**！

### 3. Token 过期时间

根据业务需求调整 Token 过期时间：

```php
// app/config/app.php
'token_expire' => 86400 * 7  // 7天（默认）
// 'token_expire' => 3600       // 1小时（高安全场景）
```

### 4. 限制 API 访问来源

如果 API 不需要对外公开，可以配置 IP 白名单：

```nginx
# 仅允许前端服务器访问
location /api/ {
    allow 1.2.3.4;  # 前端服务器 IP
    deny all;
    
    try_files $uri /index.php?$query_string;
}
```

---

## 📱 客户端开发示例

### Vue 3 + Vite

#### 1. 安装依赖

```bash
npm create vite@latest my-app -- --template vue
cd my-app
npm install axios
```

#### 2. 封装 API

创建 `src/api/request.js`：

```javascript
import axios from 'axios';

const request = axios.create({
    baseURL: 'https://api.xxx.com',
    timeout: 10000
});

// 请求拦截器
request.interceptors.request.use(config => {
    const token = localStorage.getItem('user_token');
    if (token) {
        config.headers.Authorization = token;
    }
    return config;
});

// 响应拦截器
request.interceptors.response.use(
    response => {
        const res = response.data;
        if (res.code !== 0) {
            console.error('API 错误:', res.msg);
            return Promise.reject(res.msg);
        }
        return res.data;
    },
    error => {
        console.error('请求失败:', error);
        return Promise.reject(error);
    }
);

export default request;
```

创建 `src/api/article.js`：

```javascript
import request from './request';

// 获取文章列表
export const getArticles = (params) => {
    return request.get('/api/articles', { params });
};

// 获取文章详情
export const getArticleDetail = (id) => {
    return request.get(`/api/article/${id}`);
};

// 用户登录
export const login = (data) => {
    return request.post('/api/login', data);
};
```

#### 3. 使用示例

```vue
<script setup>
import { ref, onMounted } from 'vue';
import { getArticles } from '@/api/article';

const articles = ref([]);

onMounted(async () => {
    articles.value = await getArticles({ page: 1, limit: 10 });
});
</script>

<template>
    <div v-for="article in articles" :key="article.id">
        <h2>{{ article.title }}</h2>
        <p>{{ article.author }}</p>
    </div>
</template>
```

#### 4. 构建部署

```bash
npm run build
```

将 `dist/` 目录上传到 `c.xxx.com` 服务器。

---

### React + Next.js

#### 1. 安装依赖

```bash
npx create-next-app@latest my-app
cd my-app
npm install axios
```

#### 2. 封装 API

创建 `lib/api.js`：

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'https://api.xxx.com'
});

api.interceptors.request.use(config => {
    if (typeof window !== 'undefined') {
        const token = localStorage.getItem('user_token');
        if (token) {
            config.headers.Authorization = token;
        }
    }
    return config;
});

export const getArticles = async () => {
    const res = await api.get('/api/articles');
    return res.data.data;
};

export const getArticleDetail = async (id) => {
    const res = await api.get(`/api/article/${id}`);
    return res.data.data;
};
```

#### 3. 使用示例

```jsx
// app/articles/page.js
import { getArticles } from '@/lib/api';

export default async function ArticlesPage() {
    const articles = await getArticles();
    
    return (
        <div>
            {articles.map(article => (
                <div key={article.id}>
                    <h2>{article.title}</h2>
                    <p>{article.author}</p>
                </div>
            ))}
        </div>
    );
}
```

---

### 小程序 / Uni-app

#### 1. 封装请求

创建 `utils/request.js`：

```javascript
const API_BASE = 'https://api.xxx.com';

const request = (url, options = {}) => {
    return new Promise((resolve, reject) => {
        uni.request({
            url: API_BASE + url,
            method: options.method || 'GET',
            data: options.data,
            header: {
                'Content-Type': 'application/json',
                'Authorization': uni.getStorageSync('user_token') || ''
            },
            success: (res) => {
                if (res.data.code === 0) {
                    resolve(res.data.data);
                } else {
                    uni.showToast({
                        title: res.data.msg,
                        icon: 'none'
                    });
                    reject(res.data.msg);
                }
            },
            fail: reject
        });
    });
};

export default request;
```

#### 2. 使用示例

```javascript
import request from '@/utils/request';

export default {
    data() {
        return {
            articles: []
        }
    },
    onLoad() {
        this.loadArticles();
    },
    methods: {
        async loadArticles() {
            this.articles = await request('/api/articles');
        }
    }
}
```

---

## 🎨 管理端部署优化

### 1. 使用 CDN 加速

将静态资源（Layui、wangEditor）改为 CDN 引入，已在 `index.html` 中默认使用：

```html
<!-- Layui CDN -->
<link rel="stylesheet" href="https://unpkg.com/layui@2.8.18/dist/css/layui.css">
<script src="https://unpkg.com/layui@2.8.18/dist/layui.js"></script>

<!-- wangEditor CDN -->
<script src="https://unpkg.com/@wangeditor/editor@latest/dist/index.js"></script>
```

### 2. 启用 Gzip 压缩

**Nginx 配置**：
```nginx
gzip on;
gzip_types text/css application/javascript application/json;
gzip_min_length 1000;
```

### 3. 添加版本号缓存控制

修改 `index.html` 中的资源引用：

```html
<!-- 增加版本号，强制刷新缓存 -->
<link rel="stylesheet" href="assets/css/admin.css?v=2.3.1">
<script src="assets/js/config.js?v=2.3.1"></script>
```

---

## 🧪 测试清单

部署完成后，请按以下清单测试：

### API 端测试

- [ ] 访问 `https://api.xxx.com/api/health` 返回健康检查
- [ ] 使用 Postman 测试登录接口 `POST /api/admin/login`
- [ ] 测试文件上传接口，确认返回完整 URL
- [ ] 检查 CORS 响应头是否正确

### 管理端测试

- [ ] 访问 `https://admin.xxx.com/login.html` 登录页面正常显示
- [ ] 登录成功后跳转到 `https://admin.xxx.com/index.html`
- [ ] 打开浏览器控制台，检查 API 请求是否发往 `api.xxx.com`
- [ ] 测试 CRUD 操作（新增、编辑、删除）
- [ ] 测试文件上传，确认图片能正常显示
- [ ] 测试数据导出

### 客户端测试（如有）

- [ ] 访问 `https://c.xxx.com` 或 `https://www.xxx.com`
- [ ] 测试前端页面是否能正常获取 API 数据
- [ ] 测试用户登录、退出功能

---

## 📊 性能优化建议

### 1. API 端优化

```php
// 启用 OPcache（php.ini）
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### 2. 数据库优化

- 启用 Redis 缓存（缓存热点数据）
- 配置数据库主从复制（读写分离）
- 为常用查询字段添加索引

### 3. 静态资源 CDN

- 将 `public/uploads/` 上传到 OSS（阿里云、腾讯云）
- 配置 CDN 加速
- 修改上传接口返回 CDN URL

---

## 🔄 更新部署流程

### API 端更新

```bash
# 1. 拉取最新代码
git pull

# 2. 更新依赖
composer install --no-dev

# 3. 清除 OPcache（如果开启）
sudo service php-fpm reload
```

### 管理端更新

```bash
# 1. 拉取最新代码
git pull

# 2. 如果修改了 config.js，记得更新版本号
# index.html: <script src="assets/js/config.js?v=2.3.2"></script>

# 3. 清除浏览器缓存或强制刷新 (Ctrl+F5)
```

---

## 🌐 多环境配置

### 开发环境

```javascript
// config.js
const ENV = 'development';
const API_BASE = ENV === 'development' 
    ? 'http://localhost:8000'      // 本地开发
    : 'https://api.xxx.com';        // 生产环境
```

### 使用构建工具（推荐）

如果管理端也想使用 Vite/Webpack 打包，可以：

```javascript
// vite.config.js 或 .env
VITE_API_BASE=https://api.xxx.com

// config.js
const API_BASE = import.meta.env.VITE_API_BASE;
```

---

## 📚 相关文档

- [README.md](../README.md) - 快速开始
- [DEPLOY.md](DEPLOY.md) - 单体部署指南
- [SECURITY.md](SECURITY.md) - 安全最佳实践
- [SERVER_CONFIG.md](SERVER_CONFIG.md) - 服务器配置详解

---

## 🎉 总结

### 分离部署适用场景

- ✅ **高流量场景**：API 和静态资源分离，便于负载均衡
- ✅ **多端应用**：同一 API 服务于 Web、H5、小程序、App
- ✅ **团队协作**：前后端团队独立开发部署
- ✅ **CDN 加速**：静态资源全球加速

### 单体部署适用场景

- ✅ **小型项目**：访问量不大，单机部署即可
- ✅ **快速原型**：快速开发验证，后续再分离
- ✅ **内部系统**：企业内网系统，无需分离

---

**选择适合您的部署方式，Flight Base 都能完美支持！** 🚀
