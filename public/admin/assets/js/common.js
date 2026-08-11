/**
 * 公共函数库
 */

/**
 * 加载管理员信息到顶部（后台管理）
 */
function loadAdminInfo() {
    request(ADMIN_API_PREFIX + '/info', {
        method: 'GET'
    }).then(res => {
        if (res.code === 0 && res.data) {
            const username = res.data.nickname || res.data.username || t('common.admin_default_name', '管理员');
            document.getElementById('username').textContent = username;
        } else {
            console.error('loadAdmin error:', res.msg);
            clearToken();
            location.href = (typeof getAdminLoginEntry === 'function' ? getAdminLoginEntry() : 'login.html');
        }
    }).catch(err => {
        console.error('loadAdmin error:', err);
        document.getElementById('username').textContent = t('common.load_failed', '加载失败');
    });
}

/**
 * 加载用户信息到顶部
 */
function loadUserInfo() {
    request('/api/info', {
        method: 'GET'
    }).then(res => {
        if (res.code === 0 && res.data) {
            const username = res.data.nickname || res.data.username || t('common.admin_default_name', '用户');
            document.getElementById('username').textContent = username;
        } else {
            console.error('loadUser error:', res.msg);
            document.getElementById('username').textContent = t('common.load_failed', '未知用户');
        }
    }).catch(err => {
        console.error('loadUser error:', err);
        document.getElementById('username').textContent = t('common.load_failed', '加载失败');
    });
}

/**
 * 退出登录
 */
function logout() {
    layer.confirm(t('common.logout_confirm', '确定要退出登录吗？'), {
        icon: 3,
        title: t('common.confirm_title', '提示')
    }, function(index){
        // 先记下入口，避免异步过程中被其它逻辑改写
        var loginUrl = (typeof getAdminLoginEntry === 'function' ? getAdminLoginEntry() : 'login.html');
        request(ADMIN_API_PREFIX + '/logout', {
            method: 'POST'
        }).then(res => {
            clearToken();
            location.href = loginUrl;
        });
        layer.close(index);
    });
}

/**
 * 格式化日期时间
 */
function formatDateTime(datetime) {
    if (!datetime) return '-';
    return datetime.replace('T', ' ').substring(0, 19);
}

/**
 * 格式化日期
 */
function formatDate(date) {
    if (!date) return '-';
    return date.substring(0, 10);
}

/**
 * 状态标签
 */
function statusTag(status) {
    return status == 1 
        ? '<span class="layui-badge layui-bg-green">' + t('common.status_normal', '正常') + '</span>'
        : '<span class="layui-badge layui-bg-gray">' + t('common.status_disabled', '禁用') + '</span>';
}

/**
 * 确认对话框
 */
function confirm(msg, callback) {
    layer.confirm(msg, {
        icon: 3,
        title: t('common.confirm_title', '提示')
    }, function(index){
        callback();
        layer.close(index);
    });
}

/**
 * 成功提示
 */
function successMsg(msg, callback) {
    layer.msg(msg, { icon: 1 }, callback);
}

/**
 * 错误提示
 */
function errorMsg(msg) {
    layer.msg(msg, { icon: 2 });
}

/**
 * 加载中
 */
function loading() {
    return layer.load(1, { shade: 0.3 });
}

/**
 * 关闭加载
 */
function closeLoading(index) {
    layer.close(index);
}

/**
 * 复制到剪贴板
 */
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    successMsg(t('common.copy_success', '复制成功'));
}

/** 是否窄屏（后台移动端自适应阈值） */
function isMobileViewport() {
    return window.innerWidth <= 768;
}

/**
 * 解析 layer 弹窗尺寸：移动端强制接近全屏，避免固定 800px/900px 溢出
 * @returns {[string, string]}
 */
function resolveLayerArea(width, height) {
    if (isMobileViewport()) {
        return [
            Math.max(280, Math.floor(window.innerWidth * 0.96)) + 'px',
            Math.max(320, Math.floor(window.innerHeight * 0.92)) + 'px'
        ];
    }
    return [width || '800px', height || '80%'];
}

/** 打开后再次校正位置（防 Layui 按 PC 宽计算导致左半边裁切） */
function adaptLayerToMobile(index) {
    if (!isMobileViewport() || typeof layer === 'undefined') return;
    const w = Math.max(280, Math.floor(window.innerWidth * 0.96));
    const h = Math.max(320, Math.floor(window.innerHeight * 0.92));
    layer.style(index, {
        width: w + 'px',
        height: h + 'px',
        top: Math.floor((window.innerHeight - h) / 2) + 'px',
        left: Math.floor((window.innerWidth - w) / 2) + 'px'
    });
}

/**
 * 按 ADMIN_CAPTCHA_ENABLED /login-config 隐藏登录页验证码行
 * （.env ADMIN_CAPTCHA_ENABLED=false 时后端也不再校验）
 */
function applyLoginCaptchaUi(enabled) {
    if (enabled !== false) return;
    window.ADMIN_CAPTCHA_ENABLED = false;
    window.refreshCaptcha = function () {};

    document.querySelectorAll('.captcha-row').forEach(function (row) {
        row.style.display = 'none';
        var input = row.querySelector('input[name="captcha"]');
        if (input) {
            input.removeAttribute('required');
            input.removeAttribute('lay-verify');
            input.value = '';
            input.disabled = true;
        }
    });
}

(function initLoginCaptchaGate() {
    if (!document.querySelector('.captcha-row')) return;

    function apply(flag) {
        applyLoginCaptchaUi(flag !== false);
    }

    if (typeof window.ADMIN_CAPTCHA_ENABLED === 'boolean') {
        apply(window.ADMIN_CAPTCHA_ENABLED);
        return;
    }

    fetch('/api/admin/login-config', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var enabled = !(res && res.code === 0 && res.data && res.data.captcha_enabled === false);
            window.ADMIN_CAPTCHA_ENABLED = enabled;
            apply(enabled);
        })
        .catch(function () { /* 拉取失败保持验证码可见，与后端默认开启一致 */ });
})();
