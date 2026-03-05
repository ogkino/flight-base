/**
 * 前端配置文件
 */

// API 基础地址
const API_BASE = window.location.origin;

// 管理后台 API 前缀
const ADMIN_API_PREFIX = '/api/admin';

// Token 存储键名
const TOKEN_KEY = 'admin_token';

/**
 * Cookie 域名手动覆盖（正常情况留空，自动检测）
 * 仅当使用 .com.cn / .co.uk 等二级公共后缀时需要手动填写，例如：
 *   COOKIE_DOMAIN = '.myapp.com.cn';   // 三端分离
 *   COOKIE_DOMAIN = '';                // 自动检测（默认）
 */
const COOKIE_DOMAIN = '';

/**
 * 获取 Cookie 的 domain 属性值
 *
 * 规则：
 *   - localhost 或纯 IP 地址  → 不设 domain（单服务器模式，Cookie 绑定当前 hostname 即可）
 *   - 子域名（admin.foo.com） → 返回父域 .foo.com，使 Cookie 在同父域的所有子域共享
 *   - 根域（foo.com）         → 不设 domain（浏览器默认即当前域，无需重复声明）
 *
 * 三端部署约束：API / Admin / Frontend 必须在同一父域下，例如：
 *   admin.myapp.com  |  api.myapp.com  |  www.myapp.com  → 父域 .myapp.com
 */
function getCookieDomain() {
    // 手动覆盖优先（用于 .com.cn / .co.uk 等二级公共后缀）
    if (COOKIE_DOMAIN !== '') return COOKIE_DOMAIN;
    const hostname = location.hostname;
    // localhost 或 IPv4/IPv6 地址：单机模式，无需设 domain
    if (hostname === 'localhost' || /^[\d.:]+$/.test(hostname)) {
        return '';
    }
    const parts = hostname.split('.');
    // 子域名（至少三段）：提取父域并加前缀点，如 admin.foo.com → .foo.com
    if (parts.length > 2) {
        return '.' + parts.slice(-2).join('.');
    }
    // 根域（两段，如 foo.com）：不设 domain
    return '';
}

/**
 * 获取 Token
 */
function getToken() {
    return localStorage.getItem(TOKEN_KEY) || '';
}

/**
 * 设置 Token
 * 同时写入 localStorage（AJAX 用）和 Cookie（PHP 视图页 Cookie 鉴权用）
 * Cookie 自动附加父域，支持同父域下多子域部署（三端分离）
 */
function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
    const domain    = getCookieDomain();
    const domainStr = domain ? '; domain=' + domain : '';
    document.cookie = TOKEN_KEY + '=' + encodeURIComponent(token)
        + '; path=/; SameSite=Strict' + domainStr;
}

/**
 * 清除 Token
 * 同时清除 localStorage 和 Cookie（domain 必须与写入时一致才能正确删除）
 */
function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
    const domain    = getCookieDomain();
    const domainStr = domain ? '; domain=' + domain : '';
    document.cookie = TOKEN_KEY + '=; path=/; SameSite=Strict; expires=Thu, 01 Jan 1970 00:00:00 GMT'
        + domainStr;
}

/**
 * HTTP 请求封装
 */
function request(url, options = {}) {
    const config = {
        method: options.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': getToken()
        }
    };
    
    // GET 请求参数拼接到 URL
    if (options.data) {
        if (config.method === 'GET') {
            const params = new URLSearchParams(options.data);
            url += (url.includes('?') ? '&' : '?') + params.toString();
        } else {
            config.body = JSON.stringify(options.data);
        }
    }
    
    return fetch(API_BASE + url, config)
        .then(res => res.json())
        .catch(err => {
            console.error('请求失败:', err);
            return { code: -1, msg: '网络错误' };
        });
}

/**
 * 检查登录状态
 */
function checkLogin() {
    if (!getToken()) {
        location.href = 'login.html';
        return false;
    }
    return true;
}

// 页面加载时，将 localStorage 中的 Token 同步到 Cookie（兼容登录前已有旧会话的情况）
;(function syncTokenCookie() {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
        const domain    = getCookieDomain();
        const domainStr = domain ? '; domain=' + domain : '';
        document.cookie = TOKEN_KEY + '=' + encodeURIComponent(token)
            + '; path=/; SameSite=Strict' + domainStr;
    }
})();
