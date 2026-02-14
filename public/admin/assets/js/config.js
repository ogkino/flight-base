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
 * 获取 Token
 */
function getToken() {
    return localStorage.getItem(TOKEN_KEY) || '';
}

/**
 * 设置 Token
 */
function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
}

/**
 * 清除 Token
 */
function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
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
